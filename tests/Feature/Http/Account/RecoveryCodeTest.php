<?php

declare(strict_types=1);

use App\Models\User;

it('regenerates the recovery codes, with a toast', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCodes = (array) $user->two_factor_recovery_codes;
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->post(route('account.two-factor-authentication.recovery-codes.store'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.two_factor_authentication.recovery_codes.regenerated')]);

    expect(array_intersect((array) $user->refresh()->two_factor_recovery_codes, $recoveryCodes))->toBeEmpty();
});

it('leaves recovery codes as they are without two-factor authentication enabled, with a toast', function (User $user): void {
    $recoveryCodes = $user->two_factor_recovery_codes;
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->post(route('account.two-factor-authentication.recovery-codes.store'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => trans('identity.two_factor_authentication.not_enabled')]);

    expect($user->refresh()->two_factor_recovery_codes)->toBe($recoveryCodes);
})->with([
    'pending' => fn (): User => User::factory()->withTwoFactorAuthentication()->create(['two_factor_confirmed_at' => null]),
    'disabled' => fn (): User => User::factory()->create(),
]);

it('asks for the password first', function (): void {
    $this->actingAs(User::factory()->withTwoFactorAuthentication()->create())
        ->post(route('account.two-factor-authentication.recovery-codes.store'))
        ->assertRedirect(route('password.confirm'));
});

it('sends unverified users to the verification notice', function (): void {
    signInWithConfirmedPassword(User::factory()->unverified()->withTwoFactorAuthentication()->create());

    $this->post(route('account.two-factor-authentication.recovery-codes.store'))->assertRedirect(route('verification.notice'));
});

it('sends guests to the sign-in page', function (): void {
    $this->post(route('account.two-factor-authentication.recovery-codes.store'))->assertRedirect(route('login'));
});
