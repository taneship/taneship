<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Models\User;
use Symfony\Component\Serializer\SerializerInterface;
use Tests\SoftwareAuthenticator;
use Webauthn\PublicKeyCredentialRequestOptions;

// The browser asks the confirmation page for the options of a ceremony, then signs them.
function confirmationOptions(): PublicKeyCredentialRequestOptions
{
    $options = partialReload(route('password.confirm'), 'auth/confirm-password', 'passkeyOptions')->json('props.passkeyOptions');

    return app(SerializerInterface::class)->deserialize(json_encode($options, JSON_THROW_ON_ERROR), PublicKeyCredentialRequestOptions::class, 'json');
}

it('confirms the password with a passkey of the user and leads to the dashboard', function (): void {
    $this->freezeSecond();
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    $passkey = registerPasskeyOn($authenticator, $user);

    $this->actingAs($user)
        ->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate(confirmationOptions()))])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('auth.password_confirmed_at', now()->unix())
        ->assertSessionMissing('passkeys.confirmation_options');

    expect($passkey->refresh()->credential->counter)->toBe(1)
        ->and($passkey->last_used_at?->equalTo(now()))->toBeTrue();
});

it('leads to the intended url after confirming the password', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);
    $credential = json_encode($authenticator->authenticate(confirmationOptions()));

    $this->withSession(['url.intended' => url('/somewhere')])
        ->post(route('password.confirm.passkey.store'), ['credential' => $credential])
        ->assertRedirect(url('/somewhere'));
});

it('refuses a passkey of another user', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $this->actingAs(User::factory()->create());

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate(confirmationOptions()))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a failed ceremony, and asks for a new one', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);
    $options = confirmationOptions();

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate($options, 'https://evil.example'))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('passkeys.confirmation_options');

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate($options))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a ceremony of earlier options', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);
    $credential = json_encode($authenticator->authenticate(confirmationOptions()));
    confirmationOptions();

    $this->post(route('password.confirm.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a replayed ceremony', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);
    $credential = json_encode($authenticator->authenticate(confirmationOptions()));
    $this->post(route('password.confirm.passkey.store'), ['credential' => $credential]);
    session()->forget('auth.password_confirmed_at');

    $this->post(route('password.confirm.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a passkey it does not know', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    // Created by the authenticator, never registered: as a passkey removed from the account but kept by the device.
    $authenticator->register(app(CreatePasskeyRegistrationOptions::class)->handle($user));
    $this->actingAs($user);

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate(confirmationOptions()))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a credential it cannot read', function (string $credential): void {
    $this->actingAs(User::factory()->create());
    confirmationOptions();

    $this->post(route('password.confirm.passkey.store'), ['credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('auth.password_confirmed_at');
})->with([
    'not json' => 'a passkey',
    'no id' => '{"rawId": "AAAA", "type": "public-key", "response": {}}',
    'no response' => '{"id": "AAAA", "rawId": "AAAA", "type": "public-key"}',
    'a registration' => fn (): string => json_encode(new SoftwareAuthenticator()->register(app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create()))),
]);

it('validates the form', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.passkey.store'))
        ->assertSessionHasErrors(['credential'])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('refuses a credential too long to be one, without reading it', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.passkey.store'), ['credential' => str_repeat('a', 16385)])
        ->assertSessionHasErrors(['credential' => trans('validation.max.string', ['attribute' => 'credential', 'max' => 16384])])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('confirms passwords for signed-in users only', function (): void {
    $this->post(route('password.confirm.passkey.store'))->assertRedirect(route('login'));
});

it('refuses a seventh request within a minute', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);

    foreach (range(1, 6) as $request) {
        $this->post(route('password.confirm.passkey.store'))->assertSessionHasErrors(['credential']);
    }

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate(confirmationOptions()))])
        ->assertTooManyRequests()
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('counts its requests apart from those of the password form', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $user = User::factory()->create();
    registerPasskeyOn($authenticator, $user);
    $this->actingAs($user);

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.confirm.store'), ['password' => 'wrong-password'])->assertSessionHasErrors(['password']);
    }

    $this->post(route('password.confirm.passkey.store'), ['credential' => json_encode($authenticator->authenticate(confirmationOptions()))])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('auth.password_confirmed_at');
});
