<?php

declare(strict_types=1);

use App\Actions\UpdateProfile;
use App\Data\ProfileData;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('saves the name and the address', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Smith', email: 'jane.smith@example.com'));

    expect($user->refresh()->name)->toBe('Jane Smith')
        ->and($user->email)->toBe('jane.smith@example.com');
});

it('stores the address in lowercase', function (): void {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Doe', email: 'Jane@Example.COM'));

    expect($user->refresh()->email)->toBe('jane@example.com');
});

it('clears the verification of a new address and sends it the link', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Doe', email: 'jane.smith@example.com'));

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentToTimes($user, VerifyEmail::class);
    Notification::assertSentTo(
        $user,
        VerifyEmail::class,
        fn (VerifyEmail $notification, array $channels, User $notifiable): bool => $notifiable->routeNotificationFor('mail') === 'jane.smith@example.com',
    );
});

it('keeps the verification when the address stays the same', function (string $email): void {
    Notification::fake();
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Smith', email: $email));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
})->with(['same case' => 'jane@example.com', 'other case' => 'Jane@Example.COM']);

it('removes the password reset token of the former address, and no other', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $otherUser = User::factory()->create();
    Password::createToken($user);
    Password::createToken($otherUser);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Doe', email: 'jane.smith@example.com'));

    expect(DB::table('password_reset_tokens')->pluck('email')->all())->toBe([$otherUser->email]);
});

it('keeps the password reset token when the address stays the same', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    Password::createToken($user);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Smith', email: 'jane@example.com'));

    expect(DB::table('password_reset_tokens')->pluck('email')->all())->toBe(['jane@example.com']);
});

it('refuses an address that another request takes first', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    // The other request takes the address between the validation and the save of this one.
    User::updating(function (): void {
        User::factory()->create(['email' => 'jane.smith@example.com']);
    });

    // No query follows: PostgreSQL ends the transaction of the test at the refused update.
    expect(fn () => app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Doe', email: 'jane.smith@example.com')))
        ->toThrow(EmailAlreadyTakenException::class);
});
