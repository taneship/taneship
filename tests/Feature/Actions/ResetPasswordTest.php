<?php

declare(strict_types=1);

use App\Actions\ResetPassword;
use App\Data\PasswordResetData;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('sets the new password', function (): void {
    $user = User::factory()->create();

    app(ResetPassword::class)->handle(new PasswordResetData(
        token: Password::createToken($user),
        email: $user->email,
        password: 'correct horse battery',
    ));

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});

it('cycles the remember token', function (): void {
    $user = User::factory()->create(['remember_token' => 'issued-before-the-reset']);

    app(ResetPassword::class)->handle(new PasswordResetData(
        token: Password::createToken($user),
        email: $user->email,
        password: 'correct horse battery',
    ));

    expect($user->refresh()->remember_token)->not->toBe('issued-before-the-reset')->toHaveLength(60);
});

it('fires password reset', function (): void {
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();

    app(ResetPassword::class)->handle(new PasswordResetData(
        token: Password::createToken($user),
        email: $user->email,
        password: 'correct horse battery',
    ));

    Event::assertDispatchedTimes(PasswordReset::class);
    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $user->is($event->user));
});

it('serves a token once', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    app(ResetPassword::class)->handle(new PasswordResetData(
        token: $token,
        email: $user->email,
        password: 'correct horse battery',
    ));

    expect(fn () => app(ResetPassword::class)->handle(new PasswordResetData(
        token: $token,
        email: $user->email,
        password: 'battery horse correct',
    )))->toThrow(InvalidPasswordResetTokenException::class);

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});

it('refuses an invalid token', function (): void {
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();
    Password::createToken($user);

    expect(fn () => app(ResetPassword::class)->handle(new PasswordResetData(
        token: 'invalid-token',
        email: $user->email,
        password: 'correct horse battery',
    )))->toThrow(InvalidPasswordResetTokenException::class);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});

it('refuses a token after 60 minutes', function (): void {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->travel(61)->minutes();

    expect(fn () => app(ResetPassword::class)->handle(new PasswordResetData(
        token: $token,
        email: $user->email,
        password: 'correct horse battery',
    )))->toThrow(InvalidPasswordResetTokenException::class);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('refuses the token of another address', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    expect(fn () => app(ResetPassword::class)->handle(new PasswordResetData(
        token: Password::createToken($owner),
        email: $other->email,
        password: 'correct horse battery',
    )))->toThrow(InvalidPasswordResetTokenException::class);

    expect(Hash::check('password', $owner->refresh()->password))->toBeTrue()
        ->and(Hash::check('password', $other->refresh()->password))->toBeTrue();
});

it('refuses an address without an account as it refuses an invalid token', function (): void {
    expect(fn () => app(ResetPassword::class)->handle(new PasswordResetData(
        token: 'invalid-token',
        email: 'nobody@example.com',
        password: 'correct horse battery',
    )))->toThrow(InvalidPasswordResetTokenException::class);
});
