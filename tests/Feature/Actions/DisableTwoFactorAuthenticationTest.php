<?php

declare(strict_types=1);

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Actions\DisableTwoFactorAuthentication;
use App\Actions\EnableTwoFactorAuthentication;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:10'));
});

dataset('two-factor states', [
    'pending' => fn (): User => User::factory()->withTwoFactorAuthentication()->create(['two_factor_confirmed_at' => null]),
    'enabled' => fn (): User => User::factory()->withTwoFactorAuthentication()->create(),
    'disabled' => fn (): User => User::factory()->create(),
]);

it('discards the secret and the recovery codes', function (User $user): void {
    app(DisableTwoFactorAuthentication::class)->handle($user);

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
})->with('two-factor states');

it('forgets the last step the discarded secret accepted', function (): void {
    $user = pendingTwoFactorUser();
    app(ConfirmTwoFactorAuthentication::class)->handle($user, twoFactorCode($user));

    app(DisableTwoFactorAuthentication::class)->handle($user);

    // The new secret's code falls on the step the previous secret used up.
    app(EnableTwoFactorAuthentication::class)->handle($user);
    app(ConfirmTwoFactorAuthentication::class)->handle($user, twoFactorCode($user));

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});
