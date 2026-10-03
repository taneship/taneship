<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

// A browser cannot be handed a pending sign-in: it enters the password, then reloads the challenge to get its server render.
function visitChallenge(User $user, string $mode): AwaitableWebpage
{
    return visit(route('login'))
        ->{$mode}()
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/two-factor-challenge')
        ->navigate(route('two-factor-challenge.create'));
}

it('renders the challenge page', function (string $mode, bool $isDark): void {
    visitChallenge(User::factory()->withTwoFactorAuthentication()->create(), $mode)
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.two_factor_challenge.code_description'))
        ->assertAttribute('#code', 'autocomplete', 'one-time-code')
        ->assertMissing('#recovery_code')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('asks for a recovery code instead, and for a code again', function (string $mode): void {
    visitChallenge(User::factory()->withTwoFactorAuthentication()->create(), $mode)
        ->press(trans('identity.two_factor_challenge.use_recovery_code'))
        ->assertSee(trans('identity.two_factor_challenge.recovery_code_description'))
        ->assertVisible('#recovery_code')
        ->assertMissing('#code')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3)
        ->press(trans('identity.two_factor_challenge.use_code'))
        ->assertSee(trans('identity.two_factor_challenge.code_description'))
        ->assertVisible('#code')
        ->assertMissing('#recovery_code')
        ->assertNoSmoke();
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('shows why a code is refused, and clears it', function (string $mode): void {
    $this->freezeTime();
    $user = User::factory()->withTwoFactorAuthentication()->create();

    visitChallenge($user, $mode)
        ->type('code', twoFactorCode($user, 2))
        ->press(trans('identity.two_factor_challenge.submit'))
        ->assertSee(trans('identity.two_factor_authentication.invalid_code'))
        ->assertAttribute('#code', 'aria-invalid', 'true')
        ->assertValue('#code', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('shows why a recovery code is refused, and keeps it to fix a typo', function (string $mode): void {
    visitChallenge(User::factory()->withTwoFactorAuthentication()->create(), $mode)
        ->press(trans('identity.two_factor_challenge.use_recovery_code'))
        ->type('recovery_code', 'abcdefghij-klmnopqrst')
        ->press(trans('identity.two_factor_challenge.submit'))
        ->assertSee(trans('identity.two_factor_authentication.recovery_codes.invalid'))
        // The text of an invalid field fades to red: axe would measure the color it starts from.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#recovery_code', 'aria-invalid', 'true')
        ->assertValue('#recovery_code', 'abcdefghij-klmnopqrst')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('drops the error of one field when asking for the other', function (): void {
    $this->freezeTime();
    $user = User::factory()->withTwoFactorAuthentication()->create();

    visitChallenge($user, 'inLightMode')
        ->type('code', twoFactorCode($user, 2))
        ->press(trans('identity.two_factor_challenge.submit'))
        ->assertSee(trans('identity.two_factor_authentication.invalid_code'))
        ->press(trans('identity.two_factor_challenge.use_recovery_code'))
        ->press(trans('identity.two_factor_challenge.use_code'))
        ->assertDontSee(trans('identity.two_factor_authentication.invalid_code'))
        ->assertAttribute('#code', 'aria-invalid', 'false')
        ->assertNoSmoke();
});
