<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

it('sends the mail again with a toast', function (): void {
    Notification::fake();
    $this->freezeTime();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.verification.sent')]);

    Notification::assertSentToTimes($user, VerifyEmail::class);
    Notification::assertSentTo($user, function (VerifyEmail $notification) use ($user): bool {
        $mail = $notification->toMail($user);

        expect($mail->subject)->toBe(trans('identity.verification.mail.subject'))
            ->and($mail->introLines)->toBe([trans('identity.verification.mail.instruction')])
            ->and($mail->actionText)->toBe(trans('identity.verification.mail.action'))
            ->and($mail->actionUrl)->toBe(URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
                'id' => $user->id,
                'hash' => hash('sha256', $user->email),
            ]))
            ->and($mail->outroLines)->toBe([
                trans('identity.verification.mail.expiration', ['count' => 60]),
                trans('identity.verification.mail.ignore'),
            ])
            ->and((string) $mail->render())->toContain(trans('identity.verification.mail.instruction'));

        return true;
    });
});

it('queues the mail', function (): void {
    Queue::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'));

    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn (SendQueuedNotifications $job): bool => $job->notification instanceof VerifyEmail,
    );
});

it('leads a verified user to the dashboard without sending the mail', function (): void {
    Notification::fake();

    $this->actingAs(User::factory()->create())
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlashMissing('toast');

    Notification::assertNothingSent();
});

it('sends signed-in users only', function (): void {
    Notification::fake();

    $this->post(route('verification.send'))->assertRedirect(route('login'));

    Notification::assertNothingSent();
});

it('refuses a seventh request within a minute', function (): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    foreach (range(1, 6) as $request) {
        $this->post(route('verification.send'))->assertRedirect();
    }

    $this->post(route('verification.send'))->assertTooManyRequests();

    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
});
