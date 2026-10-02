<?php

declare(strict_types=1);

use App\Actions\RegenerateRecoveryCodes;
use App\Exceptions\TwoFactorAuthenticationNotEnabledException;
use App\Models\User;

it('replaces the eight recovery codes, and nothing else', function (): void {
    $this->freezeSecond();
    $user = User::factory()->withTwoFactorAuthentication()->create(['two_factor_confirmed_at' => now()->subDay()]);
    $secret = $user->two_factor_secret;
    $recoveryCodes = (array) $user->two_factor_recovery_codes;

    app(RegenerateRecoveryCodes::class)->handle($user);

    $user->refresh();

    expect($user->two_factor_recovery_codes)->toHaveCount(8)
        ->each->toMatch('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/');

    expect(array_unique((array) $user->two_factor_recovery_codes))->toHaveCount(8)
        ->and(array_intersect((array) $user->two_factor_recovery_codes, $recoveryCodes))->toBeEmpty()
        ->and($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_confirmed_at?->equalTo(now()->subDay()))->toBeTrue();
});

it('refuses a pending setup', function (): void {
    $user = pendingTwoFactorUser();
    $recoveryCodes = $user->two_factor_recovery_codes;

    expect(fn () => app(RegenerateRecoveryCodes::class)->handle($user))
        ->toThrow(TwoFactorAuthenticationNotEnabledException::class);

    expect($user->refresh()->two_factor_recovery_codes)->toBe($recoveryCodes);
});

it('refuses a user without two-factor authentication', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(RegenerateRecoveryCodes::class)->handle($user))
        ->toThrow(TwoFactorAuthenticationNotEnabledException::class);

    expect($user->refresh()->two_factor_recovery_codes)->toBeNull();
});
