<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function passwordResetInput(User $user, array $overrides = []): array
{
    return [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'correct horse battery',
        'password_confirmation' => 'correct horse battery',
        ...$overrides,
    ];
}

it('renders the reset page for guests', function (): void {
    $this->get(route('password.reset', ['token' => 'a-token', 'email' => 'jane@example.com']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/reset-password')
            ->where('token', 'a-token')
            ->where('email', 'jane@example.com')
            ->where('passwordRules', 'minlength: 12;')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('renders the reset page without an address when the link has none', function (string $query): void {
    $this->get(route('password.reset', ['token' => 'a-token']).$query)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/reset-password')
            ->where('email', '')
            ->etc());
})->with(['no address' => '', 'a list of addresses' => '?email[]=jane@example.com']);

it('sends signed-in users from the reset page to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('password.reset', ['token' => 'a-token']))
        ->assertRedirect(route('dashboard'));
});

it('resets the password and leads to sign-in with a toast', function (): void {
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();

    $this->post(route('password.update'), passwordResetInput($user))
        ->assertRedirect(route('login'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.password.reset')]);

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
    $this->assertGuest();
    Event::assertDispatched(PasswordReset::class);
});

it('compares addresses in lowercase', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->post(route('password.update'), passwordResetInput($user, ['email' => 'Jane@Example.COM']))
        ->assertRedirect(route('login'))
        ->assertSessionHasNoErrors();

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});

it('answers on the address for an invalid token', function (): void {
    $user = User::factory()->create();

    $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->post(route('password.update'), passwordResetInput($user, ['token' => 'invalid-token']))
        ->assertRedirect(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->assertSessionHasErrors(['email' => trans('passwords.token')])
        ->assertInertiaFlashMissing('toast');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('gives an address without an account the answer of an invalid token', function (): void {
    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => 'nobody@example.com',
        'password' => 'correct horse battery',
        'password_confirmation' => 'correct horse battery',
    ])->assertSessionHasErrors(['email' => trans('passwords.token')]);
});

it('requires a password of 12 characters', function (): void {
    $user = User::factory()->create();

    $this->post(route('password.update'), passwordResetInput($user, [
        'password' => str_repeat('a', 11),
        'password_confirmation' => str_repeat('a', 11),
    ]))->assertSessionHasErrors(['password' => trans('validation.min.string', ['attribute' => 'password', 'min' => 12])]);

    $this->post(route('password.update'), passwordResetInput($user, [
        'password' => str_repeat('a', 12),
        'password_confirmation' => str_repeat('a', 12),
    ]))->assertSessionHasNoErrors();
});

it('validates the form', function (array $overrides, array $errors): void {
    $user = User::factory()->create();

    $this->post(route('password.update'), passwordResetInput($user, $overrides))->assertSessionHasErrors($errors);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
})->with([
    'empty' => [['token' => '', 'email' => '', 'password' => '', 'password_confirmation' => ''], ['token', 'email', 'password']],
    'not an address' => [['email' => 'jane'], ['email']],
    'passwords that differ' => [['password_confirmation' => 'battery horse correct'], ['password']],
]);

it('signs the other sessions out at their next request', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();
    $signedInBrowser = session()->all();

    switchBrowser();
    $this->post(route('password.update'), passwordResetInput($user))->assertRedirect(route('login'));

    switchBrowser();
    $this->withSession($signedInBrowser)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('stops a remember cookie issued before the reset from signing in', function (): void {
    $user = User::factory()->create();
    $recaller = Auth::guard()->getRecallerName();

    // Kept encrypted, as the browser holds it, and sent with one request at a time.
    $cookie = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
        ->getCookie($recaller, decrypt: false)
        ?->getValue();

    switchBrowser();
    $this->call('GET', route('dashboard'), cookies: [$recaller => $cookie])->assertOk();

    switchBrowser();
    $this->post(route('password.update'), passwordResetInput($user))->assertRedirect(route('login'));

    switchBrowser();
    $this->call('GET', route('dashboard'), cookies: [$recaller => $cookie])->assertRedirect(route('login'));
    $this->assertGuest();
});

it('refuses a seventh request within a minute', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 6) as $request) {
        $this->post(route('password.update'), passwordResetInput($user, ['token' => 'invalid-token']))
            ->assertSessionHasErrors('email');
    }

    $this->post(route('password.update'), passwordResetInput($user))->assertTooManyRequests();

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});
