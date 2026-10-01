<?php

declare(strict_types=1);

use App\Actions\SendPasswordResetLink;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a link to the account of the address', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    app(SendPasswordResetLink::class)->handle($user->email);

    Notification::assertSentToTimes($user, ResetPassword::class);
    Notification::assertSentTo(
        $user,
        fn (ResetPassword $notification): bool => Password::tokenExists($user, $notification->token),
    );
});

it('sends nothing to an address without an account', function (): void {
    Notification::fake();

    app(SendPasswordResetLink::class)->handle('nobody@example.com');

    Notification::assertNothingSent();
});

it('sends one link a minute to an address', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    app(SendPasswordResetLink::class)->handle($user->email);
    app(SendPasswordResetLink::class)->handle($user->email);

    Notification::assertSentToTimes($user, ResetPassword::class);

    $this->travel(61)->seconds();
    app(SendPasswordResetLink::class)->handle($user->email);

    Notification::assertSentToTimes($user, ResetPassword::class, 2);
});
