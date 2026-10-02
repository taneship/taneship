<?php

declare(strict_types=1);

use App\Actions\UseRecoveryCode;
use App\Exceptions\InvalidRecoveryCodeException;
use App\Models\User;

it('accepts a recovery code and removes it, and nothing else', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCodes = (array) $user->two_factor_recovery_codes;

    app(UseRecoveryCode::class)->handle($user, (string) $recoveryCodes[2]);

    expect($user->refresh()->two_factor_recovery_codes)->toBe([
        $recoveryCodes[0],
        $recoveryCodes[1],
        ...array_slice($recoveryCodes, 3),
    ]);
});

it('refuses a code that is not one of the user\'s', function (string $recoveryCode): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCodes = $user->two_factor_recovery_codes;

    expect(fn () => app(UseRecoveryCode::class)->handle($user, $recoveryCode))
        ->toThrow(InvalidRecoveryCodeException::class);

    expect($user->refresh()->two_factor_recovery_codes)->toBe($recoveryCodes);
})->with([
    'unknown' => 'abcdefghij-klmnopqrst',
    'empty' => '',
    'of another user' => fn (): string => (string) User::factory()->withTwoFactorAuthentication()->create()->two_factor_recovery_codes[0],
]);

it('refuses a recovery code already used', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCode = (string) $user->two_factor_recovery_codes[0];
    app(UseRecoveryCode::class)->handle($user, $recoveryCode);

    expect(fn () => app(UseRecoveryCode::class)->handle($user, $recoveryCode))
        ->toThrow(InvalidRecoveryCodeException::class);

    expect($user->refresh()->two_factor_recovery_codes)->toHaveCount(7);
});

it('refuses a user without recovery codes', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(UseRecoveryCode::class)->handle($user, 'abcdefghij-klmnopqrst'))
        ->toThrow(InvalidRecoveryCodeException::class);
});
