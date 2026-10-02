<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:10'));
});

it('starts the setup', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->post(route('account.two-factor-authentication.store'))
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->toHaveCount(8)
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('starts a pending setup over, with a new secret', function (): void {
    $user = pendingTwoFactorUser();
    $secret = $user->two_factor_secret;
    signInWithConfirmedPassword($user);

    $this->post(route('account.two-factor-authentication.store'));

    expect($user->refresh()->two_factor_secret)->not->toBe($secret);
});

it('leaves enabled two-factor authentication as it is, with a toast', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $secret = $user->two_factor_secret;
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->post(route('account.two-factor-authentication.store'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => trans('identity.two_factor_authentication.already_enabled')]);

    $user->refresh();

    expect($user->two_factor_secret)->toBe($secret)
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('confirms the setup with a valid code, with a toast', function (): void {
    $user = pendingTwoFactorUser();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->put(route('account.two-factor-authentication.update'), ['code' => twoFactorCode($user)])
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.two_factor_authentication.enabled')]);

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

it('refuses a wrong code on the code field', function (): void {
    $user = pendingTwoFactorUser();
    signInWithConfirmedPassword($user);

    $this->put(route('account.two-factor-authentication.update'), ['code' => twoFactorCode($user, 2)])
        ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')]);

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('refuses a code when no setup is pending', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->put(route('account.two-factor-authentication.update'), ['code' => '123456'])
        ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')]);

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('leaves enabled two-factor authentication as it is when a code confirms it again', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create(['two_factor_confirmed_at' => now()->subDay()]);
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->put(route('account.two-factor-authentication.update'), ['code' => twoFactorCode($user)])
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => trans('identity.two_factor_authentication.already_enabled')]);

    expect($user->refresh()->two_factor_confirmed_at?->equalTo(now()->subDay()))->toBeTrue();
});

it('validates the code', function (array $input): void {
    $user = pendingTwoFactorUser();
    signInWithConfirmedPassword($user);

    $this->put(route('account.two-factor-authentication.update'), $input)->assertSessionHasErrors('code');

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
})->with([
    'empty' => [[]],
    'too short' => [['code' => '12345']],
    'too long' => [['code' => '1234567']],
    'not digits' => [['code' => '12345a']],
]);

it('turns two-factor authentication off, with a toast', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->delete(route('account.two-factor-authentication.destroy'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.two_factor_authentication.disabled')]);

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

it('cancels a pending setup, with a toast', function (): void {
    $user = pendingTwoFactorUser();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->delete(route('account.two-factor-authentication.destroy'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.two_factor_authentication.canceled')]);

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('tells a user without two-factor authentication that it is off', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->from(route('account.security.edit'))
        ->delete(route('account.two-factor-authentication.destroy'))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.two_factor_authentication.disabled')]);
});

dataset('two-factor authentication requests', [
    'setup' => ['post', fn (): string => route('account.two-factor-authentication.store')],
    'confirmation' => ['put', fn (): string => route('account.two-factor-authentication.update')],
    'deactivation' => ['delete', fn (): string => route('account.two-factor-authentication.destroy')],
]);

it('asks for the password first', function (string $method, string $url): void {
    $this->actingAs(User::factory()->create())
        ->{$method}($url)
        ->assertRedirect(route('password.confirm'));
})->with('two-factor authentication requests');

it('sends unverified users to the verification notice', function (string $method, string $url): void {
    signInWithConfirmedPassword(User::factory()->unverified()->create());

    $this->{$method}($url)->assertRedirect(route('verification.notice'));
})->with('two-factor authentication requests');

it('sends guests to the sign-in page', function (string $method, string $url): void {
    $this->{$method}($url)->assertRedirect(route('login'));
})->with('two-factor authentication requests');
