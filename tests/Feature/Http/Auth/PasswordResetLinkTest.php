<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

it('renders the forgot-password page for guests', function (): void {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/forgot-password')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('sends signed-in users from the forgot-password page to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('password.request'))
        ->assertRedirect(route('dashboard'));
});

it('sends the link with a toast', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.password.sent')]);

    Notification::assertSentToTimes($user, ResetPassword::class);
    Notification::assertSentTo($user, function (ResetPassword $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        expect($mail->subject)->toBe(trans('identity.password.mail.subject'))
            ->and($mail->introLines)->toBe([trans('identity.password.mail.instruction')])
            ->and($mail->actionText)->toBe(trans('identity.password.mail.action'))
            ->and($mail->actionUrl)->toBe(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->and($mail->outroLines)->toBe([
                trans('identity.password.mail.expiration', ['count' => 60]),
                trans('identity.password.mail.ignore'),
            ])
            ->and((string) $mail->render())->toContain(trans('identity.password.mail.instruction'));

        return true;
    });
});

it('queues the mail', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn (SendQueuedNotifications $job): bool => $job->notification instanceof ResetPassword,
    );
});

it('gives the same answer whether the address has an account, has none, or asked less than a minute ago', function (string $email): void {
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.password.sent')]);
})->with([
    'an account' => fn (): string => User::factory()->create()->email,
    'no account' => 'nobody@example.com',
    'a link asked less than a minute ago' => function (): string {
        $user = User::factory()->create();
        Password::createToken($user);

        return $user->email;
    },
]);

it('compares addresses in lowercase', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->post(route('password.email'), ['email' => 'Jane@Example.COM'])->assertSessionHasNoErrors();

    Notification::assertSentToTimes($user, ResetPassword::class);
});

it('validates the form', function (array $input): void {
    Notification::fake();

    $this->post(route('password.email'), $input)->assertSessionHasErrors(['email']);

    Notification::assertNothingSent();
})->with([
    'empty' => [[]],
    'not an address' => [['email' => 'jane']],
]);

it('refuses a seventh request within a minute', function (): void {
    Notification::fake();

    foreach (range(1, 6) as $request) {
        $this->post(route('password.email'), ['email' => "jane{$request}@example.com"])->assertRedirect();
    }

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertTooManyRequests();

    Notification::assertNothingSent();
});
