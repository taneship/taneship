<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Serializer\SerializerInterface;
use Tests\SoftwareAuthenticator;
use Webauthn\PublicKeyCredentialRequestOptions;

// The browser asks the sign-in page for the options of a ceremony, then signs them.
function authenticationOptions(): PublicKeyCredentialRequestOptions
{
    $options = partialReload(route('login'), 'auth/login', 'passkeyOptions')->json('props.passkeyOptions');

    return app(SerializerInterface::class)->deserialize(json_encode($options, JSON_THROW_ON_ERROR), PublicKeyCredentialRequestOptions::class, 'json');
}

it('signs the owner of the passkey in and leads to the dashboard', function (): void {
    $this->freezeSecond();
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    $passkey = registerPasskeyOn($authenticator, $user);

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate(authenticationOptions()))])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('passkeys.authentication_options')
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
    expect($passkey->refresh()->credential->counter)->toBe(1)
        ->and($passkey->last_used_at?->equalTo(now()))->toBeTrue();
});

it('leads to the intended url after signing in', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $credential = json_encode($authenticator->authenticate(authenticationOptions()));

    $this->withSession(['url.intended' => url('/somewhere')])
        ->post(route('login.passkey.store'), ['credential' => $credential])
        ->assertRedirect(url('/somewhere'));
});

it('regenerates the session when signing in', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $credential = json_encode($authenticator->authenticate(authenticationOptions()));
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('login.passkey.store'), ['credential' => $credential]);

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);
});

it('remembers the user when asked', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate(authenticationOptions())), 'remember' => true])
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

it('refuses a failed ceremony, and asks for a new one', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $options = authenticationOptions();

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate($options, 'https://evil.example'))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('passkeys.authentication_options');

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate($options))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    $this->assertGuest();
});

it('refuses a ceremony of earlier options', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $credential = json_encode($authenticator->authenticate(authenticationOptions()));
    authenticationOptions();

    $this->post(route('login.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    $this->assertGuest();
});

it('refuses a replayed ceremony', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $credential = json_encode($authenticator->authenticate(authenticationOptions()));
    $this->post(route('login.passkey.store'), ['credential' => $credential]);
    Auth::logout();

    $this->post(route('login.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    $this->assertGuest();
});

it('refuses a passkey it does not know', function (): void {
    $authenticator = new SoftwareAuthenticator;
    // Created by the authenticator, never registered: as a passkey removed from the account but kept by the device.
    $authenticator->register(app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create()));

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate(authenticationOptions()))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    $this->assertGuest();
});

it('refuses a credential it cannot read', function (string $credential): void {
    authenticationOptions();

    $this->post(route('login.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    $this->assertGuest();
})->with([
    'not json' => 'a passkey',
    'no id' => '{"rawId": "AAAA", "type": "public-key", "response": {}}',
    'no response' => '{"id": "AAAA", "rawId": "AAAA", "type": "public-key"}',
    'a registration' => fn (): string => json_encode(new SoftwareAuthenticator()->register(app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create()))),
]);

it('validates the form', function (): void {
    $this->post(route('login.passkey.store'))->assertSessionHasErrors(['credential']);

    $this->assertGuest();
});

it('refuses a credential too long to be one, without reading it', function (): void {
    $this->post(route('login.passkey.store'), ['credential' => str_repeat('a', 16385)])
        ->assertSessionHasErrors(['credential' => trans('validation.max.string', ['attribute' => 'credential', 'max' => 16384])]);

    $this->assertGuest();
});

it('refuses an eleventh request within a minute', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());

    // A sign-in that succeeds signs the browser in, and guest would answer before the limit does.
    foreach (range(1, 10) as $request) {
        $this->post(route('login.passkey.store'))->assertSessionHasErrors(['credential']);
    }

    $this->post(route('login.passkey.store'), ['credential' => json_encode($authenticator->authenticate(authenticationOptions()))])
        ->assertTooManyRequests();

    $this->assertGuest();
});

it('counts its requests apart from those of the other forms', function (): void {
    foreach (range(1, 6) as $request) {
        $this->post(route('login.passkey.store'))->assertSessionHasErrors(['credential']);
    }

    // The forgot-password form allows six requests a minute of its own.
    $this->post(route('password.email'))->assertSessionHasErrors(['email']);
});

it('sends signed-in users to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('login.passkey.store'))
        ->assertRedirect(route('dashboard'));
});
