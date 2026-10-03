<?php

declare(strict_types=1);

use App\Models\User;
use Tests\VirtualAuthenticator;

it('renders the sign-in page in light mode', function (): void {
    visit(route('login'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.login.description'))
        ->assertAttribute('#email', 'autocomplete', 'username webauthn')
        ->assertSee(trans('identity.login.passkey'))
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
        ->assertAttribute('#email', 'autocomplete', 'username webauthn')
        ->assertSee(trans('identity.login.passkey'))
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
        // The text of an invalid field fades to red: axe would measure the color it starts from.
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

it('shows why the browser could not use a passkey', function (string $mode): void {
    // Browser tests are served on 127.0.0.1, an address WebAuthn refuses as relying party.
    visit(route('login'))
        ->{$mode}()
        ->press(trans('identity.login.passkey'))
        ->assertSee(trans('identity.use_passkey.not_used'))
        ->assertAttribute('button[aria-describedby="credential-error"]', 'type', 'button')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('ends the request of the suggestions quietly when leaving the page', function (): void {
    VirtualAuthenticator::visit(route('login'))
        // The page waits for a passkey from the suggestions once its options arrive.
        ->assertScript('history.state.page.props.passkeyOptions !== undefined')
        ->click(trans('identity.login.sign_up'))
        ->assertPathIs('/register')
        ->assertNoSmoke();
});

it('ends the request of the suggestions quietly when the button replaces it', function (): void {
    // The authenticator holds no passkey: the dialog of the button ends as if the user closed it.
    VirtualAuthenticator::visit(route('login'))
        ->assertScript('history.state.page.props.passkeyOptions !== undefined')
        ->press(trans('identity.login.passkey'))
        ->assertButtonEnabled(trans('identity.login.passkey'))
        ->assertMissing('#credential-error')
        ->assertNoSmoke();
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
