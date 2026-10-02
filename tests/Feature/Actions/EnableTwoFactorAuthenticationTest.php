<?php

declare(strict_types=1);

use App\Actions\EnableTwoFactorAuthentication;
use App\Exceptions\TwoFactorAuthenticationAlreadyEnabledException;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

it('generates a 160-bit secret and eight recovery codes, and leaves the setup pending', function (): void {
    $user = User::factory()->create();

    app(EnableTwoFactorAuthentication::class)->handle($user);

    $user->refresh();

    // Base32 holds five bits a character: 32 characters make 160 bits.
    expect($user->two_factor_secret)->toMatch('/^[A-Z2-7]{32}$/')
        ->and($user->two_factor_recovery_codes)->toHaveCount(8)
        ->each->toMatch('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/');

    expect(array_unique((array) $user->two_factor_recovery_codes))->toHaveCount(8)
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('discards the secret and the recovery codes of a pending setup', function (): void {
    $user = User::factory()->create();
    app(EnableTwoFactorAuthentication::class)->handle($user);
    $secret = $user->two_factor_secret;
    $recoveryCodes = $user->two_factor_recovery_codes;

    app(EnableTwoFactorAuthentication::class)->handle($user);

    $user->refresh();

    expect($user->two_factor_secret)->not->toBe($secret)
        ->and($user->two_factor_recovery_codes)->not->toBe($recoveryCodes)
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

it('refuses two-factor authentication already enabled', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $secret = $user->two_factor_secret;
    $recoveryCodes = $user->two_factor_recovery_codes;

    expect(fn () => app(EnableTwoFactorAuthentication::class)->handle($user))
        ->toThrow(TwoFactorAuthenticationAlreadyEnabledException::class);

    $user->refresh();

    expect($user->two_factor_secret)->toBe($secret)
        ->and($user->two_factor_recovery_codes)->toBe($recoveryCodes)
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('encrypts the secret and the recovery codes at rest', function (): void {
    $user = User::factory()->create();

    app(EnableTwoFactorAuthentication::class)->handle($user);

    $row = DB::table('users')->where('id', $user->id)->sole(['two_factor_secret', 'two_factor_recovery_codes']);

    expect(Crypt::decryptString($row->two_factor_secret))->toBe($user->two_factor_secret)
        ->and(json_decode(Crypt::decryptString($row->two_factor_recovery_codes), associative: true))->toBe($user->two_factor_recovery_codes);
});

it('hides the two-factor columns from serialization', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create()->refresh();

    expect($user->toArray())
        ->not->toHaveKey('two_factor_secret')
        ->not->toHaveKey('two_factor_recovery_codes')
        ->not->toHaveKey('two_factor_confirmed_at');
});
