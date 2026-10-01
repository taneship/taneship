<?php

declare(strict_types=1);

use App\Models\User;

it('renders the sign-in page in light mode', function (): void {
    visit(route('login'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.login.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the sign-in page in dark mode', function (): void {
    visit(route('login'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.login.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the error of a wrong pair', function (string $mode): void {
    $user = User::factory()->create();

    visit(route('login'))
        ->{$mode}()
        ->type('email', $user->email)
        ->type('password', 'wrong-password')
        ->press('[type="submit"]')
        ->assertSee(trans('auth.failed'))
        // The button fades back in when the request ends: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertValue('password', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('signs in, remembering the user when asked', function (): void {
    $user = User::factory()->create(['remember_token' => null]);

    visit(route('login'))
        ->type('email', $user->email)
        ->type('password', 'password')
        ->click(trans('identity.login.remember'))
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->assertNoSmoke();

    expect($user->refresh()->remember_token)->not->toBeNull();
});

it('links to the forgot-password page', function (): void {
    visit(route('login'))
        ->click(trans('identity.login.forgot_password'))
        ->assertPathIs('/forgot-password')
        ->assertNoSmoke();
});

it('links to the sign-up page', function (): void {
    visit(route('login'))
        ->click(trans('identity.login.sign_up'))
        ->assertPathIs('/register')
        ->assertNoSmoke();
});
