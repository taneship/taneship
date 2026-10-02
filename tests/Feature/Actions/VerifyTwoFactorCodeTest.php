<?php

declare(strict_types=1);

use App\Actions\VerifyTwoFactorCode;
use App\Exceptions\InvalidTwoFactorCodeException;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    // Ten seconds into a step, far from the real clock: an action reading google2fa's own clock fails.
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:10'));
});

it('accepts the code of the current step', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user));
})->throwsNoExceptions();

it('accepts a code one step away', function (int $steps): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user, $steps));
})->with([
    'behind' => -1,
    'ahead' => 1,
])->throwsNoExceptions();

it('refuses a code two steps away', function (int $steps): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user, $steps)))
        ->toThrow(InvalidTwoFactorCodeException::class);
})->with([
    'behind' => -2,
    'ahead' => 2,
]);

it('refuses a code already accepted', function (int $steps): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $code = twoFactorCode($user, $steps);
    app(VerifyTwoFactorCode::class)->handle($user, $code);

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, $code))
        ->toThrow(InvalidTwoFactorCodeException::class);
})->with([
    'behind' => -1,
    'current' => 0,
    'ahead' => 1,
]);

it('refuses the code of a step before the last accepted', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user));

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user, -1)))
        ->toThrow(InvalidTwoFactorCodeException::class);
});

it('remembers an accepted step as long as the window covers it', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:00'));
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $code = twoFactorCode($user, 1);
    app(VerifyTwoFactorCode::class)->handle($user, $code);

    // The last second of the step after the code's, where the window still covers the code.
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:31:29'));

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, $code))
        ->toThrow(InvalidTwoFactorCodeException::class);
});

it('keeps the accepted steps of each user apart', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $otherUser = User::factory()->withTwoFactorAuthentication()->create();
    app(VerifyTwoFactorCode::class)->handle($user, twoFactorCode($user));

    app(VerifyTwoFactorCode::class)->handle($otherUser, twoFactorCode($otherUser));
})->throwsNoExceptions();

it('refuses a user without a secret', function (): void {
    $user = User::factory()->create();

    expect(fn () => app(VerifyTwoFactorCode::class)->handle($user, '123456'))
        ->toThrow(InvalidTwoFactorCodeException::class);
});
