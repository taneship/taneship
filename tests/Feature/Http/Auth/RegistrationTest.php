<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function signUpInput(array $overrides = []): array
{
    return [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'correct horse battery',
        'password_confirmation' => 'correct horse battery',
        ...$overrides,
    ];
}

it('renders the sign-up page for guests', function (): void {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/register')
            ->where('passwordRules', 'minlength: 12;')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('sends signed-in users from the sign-up page to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('register'))
        ->assertRedirect(route('dashboard'));
});

it('signs the new user in and leads to the dashboard', function (): void {
    Event::fake([Registered::class]);

    $this->post(route('register.store'), signUpInput())
        ->assertRedirect(route('dashboard'));

    $user = User::query()->sole();
    expect($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com');
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

it('stores the address in lowercase', function (): void {
    $this->post(route('register.store'), signUpInput(['email' => 'Jane@Example.COM']));

    expect(User::query()->sole()->email)->toBe('jane@example.com');
});

it('regenerates the session when signing up', function (): void {
    $this->startSession();
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('register.store'), signUpInput());

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);
});

it('refuses an address already taken, whatever its case', function (string $email): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->from(route('register'))
        ->post(route('register.store'), signUpInput(['email' => $email]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors(['email' => trans('validation.unique', ['attribute' => 'email'])]);

    $this->assertGuest();
    expect(User::query()->count())->toBe(1);
})->with(['same case' => 'jane@example.com', 'other case' => 'Jane@Example.COM']);

it('requires a password of 12 characters', function (): void {
    $this->post(route('register.store'), signUpInput([
        'password' => str_repeat('a', 11),
        'password_confirmation' => str_repeat('a', 11),
    ]))->assertSessionHasErrors(['password' => trans('validation.min.string', ['attribute' => 'password', 'min' => 12])]);

    $this->post(route('register.store'), signUpInput([
        'password' => str_repeat('a', 12),
        'password_confirmation' => str_repeat('a', 12),
    ]))->assertSessionHasNoErrors();
});

it('limits the name and the address to 255 characters', function (): void {
    // A valid address of 260 characters: the email rule accepts it, the length rule does not.
    $email = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 63).'.com';

    $this->post(route('register.store'), signUpInput(['name' => str_repeat('a', 256), 'email' => $email]))
        ->assertSessionHasErrors([
            'name' => trans('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'email' => trans('validation.max.string', ['attribute' => 'email', 'max' => 255]),
        ]);

    $this->assertGuest();
});

it('validates the form', function (array $input, array $errors): void {
    $this->post(route('register.store'), $input)->assertSessionHasErrors($errors);

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
})->with([
    'empty' => [[], ['name', 'email', 'password']],
    'not an address' => [signUpInput(['email' => 'jane']), ['email']],
    'passwords that differ' => [signUpInput(['password_confirmation' => 'battery horse correct']), ['password']],
]);
