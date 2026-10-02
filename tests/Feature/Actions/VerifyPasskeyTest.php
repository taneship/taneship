<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Actions\CreatePasskeyRegistrationOptions;
use App\Actions\VerifyPasskey;
use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\PasskeyOfAnotherUserException;
use App\Exceptions\UnknownPasskeyException;
use App\Models\Passkey;
use App\Models\User;
use Tests\SoftwareAuthenticator;

it('finds the passkey of a valid assertion', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    $verified = app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options)), $options);

    expect($verified->is($passkey))->toBeTrue();
});

it('records the counter and the last use', function (): void {
    $this->freezeSecond();
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options)), $options);

    expect($passkey->refresh()->credential->counter)->toBe(1)
        ->and($passkey->last_used_at?->equalTo(now()))->toBeTrue();
});

it('accepts a passkey of the given user', function (): void {
    $user = User::factory()->create();
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, $user);
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle($user);

    $verified = app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options)), $options, $user);

    expect($verified->is($passkey))->toBeTrue();
});

it('refuses a passkey of another user when given a user', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $user = User::factory()->create();
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle($user);

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options)), $options, $user))
        ->toThrow(PasskeyOfAnotherUserException::class);

    expect($passkey->refresh()->last_used_at)->toBeNull();
});

it('refuses an unknown passkey', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $authenticator->register(app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create()));
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options)), $options))
        ->toThrow(UnknownPasskeyException::class);
});

it('refuses a tampered signature', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();
    $credential = $authenticator->authenticate($options);

    // The last byte belongs to the signature's s value: the DER envelope stays readable.
    $signature = base64_decode(strtr($credential['response']['signature'], '-_', '+/'));
    $signature[-1] = chr(ord($signature[-1]) ^ 1);
    $credential['response']['signature'] = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($credential), $options))
        ->toThrow(InvalidPasskeyAssertionException::class, 'Invalid signature.');

    expect($passkey->refresh()->last_used_at)->toBeNull();
});

it('refuses an assertion made on another origin', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options, 'https://evil.example')), $options))
        ->toThrow(InvalidPasskeyAssertionException::class, 'Invalid origin.');
});

it('refuses an assertion whose client data are those of a registration', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options, type: 'webauthn.create')), $options))
        ->toThrow(InvalidPasskeyAssertionException::class, 'the client data answer no authentication.');

    expect($passkey->refresh()->last_used_at)->toBeNull();
});

it('refuses an assertion made in a page framed by another site', function (): void {
    $authenticator = new SoftwareAuthenticator;
    $passkey = registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle(publicKeyCredential($authenticator->authenticate($options, isCrossOrigin: true)), $options))
        ->toThrow(InvalidPasskeyAssertionException::class, 'the ceremony ran in a page framed by another site.');

    expect($passkey->refresh()->last_used_at)->toBeNull();
});

it('refuses an assertion made for another challenge', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $credential = publicKeyCredential($authenticator->authenticate(app(CreatePasskeyAuthenticationOptions::class)->handle()));

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle($credential, app(CreatePasskeyAuthenticationOptions::class)->handle()))
        ->toThrow(InvalidPasskeyAssertionException::class, 'Invalid challenge.');
});

it('refuses a replayed assertion, whose counter did not move', function (): void {
    $authenticator = new SoftwareAuthenticator;
    registerPasskeyOn($authenticator, User::factory()->create());
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();
    $credential = publicKeyCredential($authenticator->authenticate($options));
    app(VerifyPasskey::class)->handle($credential, $options);

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle($credential, $options))
        ->toThrow(InvalidPasskeyAssertionException::class, 'Invalid counter.');
});

it('refuses an attestation', function (): void {
    $user = User::factory()->create();
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register(app(CreatePasskeyRegistrationOptions::class)->handle($user)));
    $options = app(CreatePasskeyAuthenticationOptions::class)->handle();

    expect(fn (): Passkey => app(VerifyPasskey::class)->handle($credential, $options))
        ->toThrow(InvalidPasskeyAssertionException::class);
});
