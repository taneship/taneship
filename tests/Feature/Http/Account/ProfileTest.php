<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\EmailChanged;
use App\Notifications\VerifyEmail;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Symfony\Component\Mailer\SentMessage;

it('renders the profile page for a signed-in user', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->actingAs($user)
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('account/profile')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true))
            ->where('profile', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
            ->where('hasVerifiedEmail', true));
});

it('renders the profile page for a user whose address is unverified', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('account.profile.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('account/profile')
            ->where('hasVerifiedEmail', false));
});

it('sends guests to the sign-in page', function (): void {
    $this->get(route('account.profile.edit'))->assertRedirect(route('login'));
    $this->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => 'jane@example.com'])->assertRedirect(route('login'));
});

it('turns guests away', function (): void {
    $this->getJson(route('account.profile.edit'))->assertUnauthorized();
});

it('redirects the account settings to the profile page', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('account'))
        ->assertRedirect(route('account.profile.edit'));
});

it('sends guests from the account settings to the sign-in page', function (): void {
    $this->get(route('account'))->assertRedirect(route('login'));
});

it('saves the profile and leads back with a toast', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->actingAs($user)
        ->from(route('account.profile.edit'))
        ->put(route('account.profile.update'), ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com'])
        ->assertRedirect(route('account.profile.edit'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('account.profile.updated')]);

    expect($user->refresh()->name)->toBe('Jane Smith')
        ->and($user->email)->toBe('jane.smith@example.com');
});

it('asks the new address to be verified', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)
        ->put(route('account.profile.update'), ['name' => $user->name, 'email' => 'jane.smith@example.com'])
        ->assertSessionHasNoErrors();

    $this->get(route('account.profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('hasVerifiedEmail', false));

    Notification::assertSentToTimes($user, VerifyEmail::class);
});

it('tells the former address that the address changed', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => 'jane.smith@example.com']);

    Notification::assertSentOnDemand(EmailChanged::class, function (EmailChanged $notification, array $channels, AnonymousNotifiable $notifiable): bool {
        $mail = $notification->toMail($notifiable);

        expect($notifiable->routes)->toBe(['mail' => 'jane@example.com'])
            ->and($mail->subject)->toBe(trans('account.profile.email_changed.subject'))
            ->and($mail->introLines)->toBe([
                trans('account.profile.email_changed.notice', ['name' => config('app.name'), 'email' => 'jane.smith@example.com']),
                trans('account.profile.email_changed.ignore'),
                trans('account.profile.email_changed.recover'),
            ])
            ->and((string) $mail->render())->toContain('jane.smith@example.com');

        return true;
    });
});

it('sends each address its mail once a worker runs', function (): void {
    config(['queue.default' => 'database']);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => 'jane.smith@example.com']);

    expect(DB::table('jobs')->count())->toBe(2)
        ->and($this->artisan('queue:work', ['--stop-when-empty' => true]))->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);

    $subjectsByAddress = collect(Mail::mailer()->getSymfonyTransport()->messages())
        ->mapWithKeys(fn (SentMessage $message): array => [
            $message->getEnvelope()->getRecipients()[0]->getAddress() => $message->getOriginalMessage()->getSubject(),
        ])
        ->all();

    expect($subjectsByAddress)->toBe([
        'jane@example.com' => trans('account.profile.email_changed.subject'),
        'jane.smith@example.com' => trans('identity.verification.mail.subject'),
    ]);
});

it('shares the new name with the next page', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)->put(route('account.profile.update'), ['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $this->get(route('account.profile.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('user', ['name' => 'Jane Smith', 'email' => 'jane@example.com'])
            ->where('profile', ['name' => 'Jane Smith', 'email' => 'jane@example.com']));
});

it('stores the address in lowercase', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => 'Jane@Example.COM']);

    expect($user->refresh()->email)->toBe('jane@example.com');
});

it('keeps the address of the user, whatever its case', function (string $email): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)
        ->put(route('account.profile.update'), ['name' => 'Jane Smith', 'email' => $email])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->name)->toBe('Jane Smith')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
})->with(['same case' => 'jane@example.com', 'other case' => 'Jane@Example.COM']);

it('refuses an address another user has, whatever its case', function (string $email): void {
    User::factory()->create(['email' => 'john@example.com']);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->actingAs($user)
        ->from(route('account.profile.edit'))
        ->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => $email])
        ->assertRedirect(route('account.profile.edit'))
        ->assertSessionHasErrors(['email' => trans('validation.unique', ['attribute' => 'email'])]);

    expect($user->refresh()->email)->toBe('jane@example.com');
})->with(['same case' => 'john@example.com', 'other case' => 'John@Example.COM']);

it('refuses an address that another request takes first, as one already taken', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    // The other request takes the address between the validation and the save of this one.
    User::updating(function (): void {
        User::factory()->create(['email' => 'jane.smith@example.com']);
    });

    // No query follows: PostgreSQL ends the transaction of the test at the refused update.
    $this->actingAs($user)
        ->from(route('account.profile.edit'))
        ->put(route('account.profile.update'), ['name' => 'Jane Doe', 'email' => 'jane.smith@example.com'])
        ->assertRedirect(route('account.profile.edit'))
        ->assertSessionHasErrors(['email' => trans('validation.unique', ['attribute' => 'email'])]);
});

it('limits the name and the address to 255 characters', function (): void {
    // A valid address of 260 characters: the email rule accepts it, the length rule does not.
    $email = str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 63).'.com';

    $this->actingAs(User::factory()->create())
        ->put(route('account.profile.update'), ['name' => str_repeat('a', 256), 'email' => $email])
        ->assertSessionHasErrors([
            'name' => trans('validation.max.string', ['attribute' => 'name', 'max' => 255]),
            'email' => trans('validation.max.string', ['attribute' => 'email', 'max' => 255]),
        ]);
});

it('validates the form', function (array $input, array $errors): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->actingAs($user)
        ->put(route('account.profile.update'), $input)
        ->assertSessionHasErrors($errors);

    expect($user->refresh()->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com');
})->with([
    'empty' => [[], ['name', 'email']],
    'not an address' => [['name' => 'Jane Smith', 'email' => 'jane'], ['email']],
]);

it('refuses a seventh request within a minute', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe']);
    $this->actingAs($user);

    foreach (range(1, 6) as $request) {
        $this->put(route('account.profile.update'), [])->assertSessionHasErrors(['name', 'email']);
    }

    $this->put(route('account.profile.update'), ['name' => 'Jane Smith', 'email' => $user->email])->assertTooManyRequests();

    expect($user->refresh()->name)->toBe('Jane Doe');
});
