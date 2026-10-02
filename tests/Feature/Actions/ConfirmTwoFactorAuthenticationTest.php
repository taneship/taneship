<?php

declare(strict_types=1);

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Actions\EnableTwoFactorAuthentication;
use App\Actions\VerifyTwoFactorCode;
use App\Exceptions\InvalidTwoFactorCodeException;
use App\Exceptions\TwoFactorAuthenticationAlreadyEnabledException;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:10'));
});

function pendingTwoFactorUser(): User
{
    $user = User::factory()->create();
    app(EnableTwoFactorAuthentication::class)->handle($user);

    return $user;
}

it('records the confirmation for a valid code', function (): void {
    $user = pendingTwoFactorUser();

    app(ConfirmTwoFactorAuthentication::class)->handle($user, twoFactorCode($user));

    $user->refresh();

    expect($user->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($user->two_factor_confirmed_at?->equalTo(now()))->toBeTrue();
});

it('refuses a code outside the window, and leaves the setup pending', function (): void {
    $user = pendingTwoFactorUser();

    expect(fn () => app(ConfirmTwoFactorAuthentication::class)->handle($user, twoFactorCode($user, 2)))
        ->toThrow(InvalidTwoFactorCodeException::class);

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});

it('refuses a user who started no setup', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(ConfirmTwoFactorAuthentication::class)->handle($user, '123456'))
        ->toThrow(InvalidTwoFactorCodeException::class);

    expect($user->refresh()->two_factor_confirmed_at)->toBeNull();
});

it('refuses two-factor authentication already enabled', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create(['two_factor_confirmed_at' => now()->subDay()]);

    expect(fn () => app(ConfirmTwoFactorAuthentication::class)->handle($user, twoFactorCode($user)))
        ->toThrow(TwoFactorAuthenticationAlreadyEnabledException::class);

    expect($user->refresh()->two_factor_confirmed_at?->equalTo(now()->subDay()))->toBeTrue();
});

it('uses up the code it accepts', function (): void {
    $user = pendingTwoFactorUser();
    $code = twoFactorCode($user);

    app(ConfirmTwoFactorAuthentication::class)->handle($user, $code);

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, $code))
        ->toThrow(InvalidTwoFactorCodeException::class);
});
