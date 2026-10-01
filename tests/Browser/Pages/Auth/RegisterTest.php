<?php

declare(strict_types=1);

use App\Models\User;

it('renders the sign-up page in light mode', function (): void {
    visit(route('register'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.register.description'))
        ->assertAttribute('#password', 'passwordrules', 'minlength: 12;')
        ->assertAttribute('#password_confirmation', 'passwordrules', 'minlength: 12;')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the sign-up page in dark mode', function (): void {
    visit(route('register'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.register.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the error of an address already taken', function (string $mode): void {
    $user = User::factory()->create();

    visit(route('register'))
        ->{$mode}()
        ->type('name', 'Jane Doe')
        ->type('email', $user->email)
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertSee(trans('validation.unique', ['attribute' => 'email']))
        // The button fades back in when the request ends: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertValue('password', '')
        ->assertValue('password_confirmation', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('signs up and asks to verify the email address', function (): void {
    visit(route('register'))
        ->type('name', 'Jane Doe')
        ->type('email', 'jane@example.com')
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/email/verify')
        ->assertSee(trans('identity.verify_email.title'))
        ->assertNoSmoke();
});

it('links to the sign-in page', function (): void {
    visit(route('register'))
        ->click(trans('identity.register.sign_in'))
        ->assertPathIs('/login')
        ->assertNoSmoke();
});
