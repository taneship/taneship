<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\VirtualAuthenticator;

// The passkey stays in the authenticator of this page: the journey goes on in it.
function registerPasskeyThenSignOut(User $user): PendingAwaitablePage
{
    test()->actingAs($user);

    $page = VirtualAuthenticator::visit(route('account.security.edit'));

    $page->assertPathIs('/confirm-password')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/account/security')
        ->type('name', 'Chrome')
        ->press(trans('identity.security.passkeys.add'))
        ->assertSee(trans('identity.passkeys.added'))
        ->assertSeeIn('section > ul', 'Chrome')
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/');

    return $page;
}

it('registers a passkey, signs out and signs in with it', function (): void {
    $user = User::factory()->create(['remember_token' => null]);
    $page = registerPasskeyThenSignOut($user);

    // Chrome's virtual authenticator answers the request of the suggestions at once. As in a browser
    // without suggestions, the button is then the only way in.
    $page->script('PublicKeyCredential.isConditionalMediationAvailable = async () => false');

    $page->click(trans('identity.welcome.sign_in'))
        ->assertPathIs('/login')
        ->click(trans('identity.login.remember'))
        ->press(trans('identity.login.passkey'))
        ->assertPathIs('/dashboard')
        ->assertSee($user->name)
        ->assertNoSmoke();

    expect($user->passkeys()->sole()->last_used_at)->not->toBeNull()
        ->and($user->refresh()->remember_token)->not->toBeNull();
});

it('signs in with a passkey from the suggestions of the email field', function (): void {
    $user = User::factory()->create();

    // Chrome's virtual authenticator picks the passkey among the suggestions as soon as the page asks.
    registerPasskeyThenSignOut($user)
        ->click(trans('identity.welcome.sign_in'))
        ->assertPathIs('/dashboard')
        ->assertSee($user->name)
        ->assertNoSmoke();

    expect($user->passkeys()->sole()->last_used_at)->not->toBeNull();
});

it('confirms the password with a passkey', function (): void {
    $user = User::factory()->create();

    // Signing out ended the confirmation of the registration: the new session asks again.
    registerPasskeyThenSignOut($user)
        ->click(trans('identity.welcome.sign_in'))
        ->assertPathIs('/dashboard')
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->click(trans('identity.account_layout.security'))
        ->assertPathIs('/confirm-password')
        ->press(trans('identity.confirm_password.passkey'))
        ->assertPathIs('/account/security')
        ->assertSeeIn('section > ul', 'Chrome')
        ->assertNoSmoke();
});
