<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Actions\CreatePasskeyRegistrationOptions;
use App\Actions\RegisterPasskey;
use App\Data\PasskeyRegistrationData;
use App\Exceptions\InvalidPasskeyAttestationException;
use App\Exceptions\PasskeyAlreadyRegisteredException;
use App\Models\Passkey;
use App\Models\User;
use Tests\SoftwareAuthenticator;

it('stores the passkey of the user under its name', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = new SoftwareAuthenticator('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')->register($options);

    $passkey = app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', publicKeyCredential($credential)), $options)->refresh();

    expect($passkey->user()->is($user))->toBeTrue()
        ->and($passkey->name)->toBe('MacBook Pro')
        ->and($passkey->credential_id)->toBe($credential['id'])
        ->and($passkey->credential->userHandle)->toBe($options->user->id)
        ->and($passkey->credential->aaguid->toRfc4122())->toBe('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')
        ->and($passkey->last_used_at)->toBeNull();
});

it('refuses a credential already registered', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register($options));
    app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options);

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('Work laptop', $credential), $options))
        ->toThrow(PasskeyAlreadyRegisteredException::class);

    expect($user->passkeys()->pluck('name')->all())->toBe(['MacBook Pro']);
});

it('refuses an attestation made for another challenge', function (): void {
    $user = User::factory()->create();
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register(app(CreatePasskeyRegistrationOptions::class)->handle($user)));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), app(CreatePasskeyRegistrationOptions::class)->handle($user)))
        ->toThrow(InvalidPasskeyAttestationException::class, 'Invalid challenge.');

    expect(Passkey::query()->exists())->toBeFalse();
});

it('refuses an attestation made on another origin', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register($options, 'https://evil.example'));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options))
        ->toThrow(InvalidPasskeyAttestationException::class, 'Invalid origin.');

    expect(Passkey::query()->exists())->toBeFalse();
});

it('refuses an attestation whose client data are those of an authentication', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register($options, type: 'webauthn.get'));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options))
        ->toThrow(InvalidPasskeyAttestationException::class, 'the client data answer no registration.');

    expect(Passkey::query()->exists())->toBeFalse();
});

it('refuses an attestation made in a page framed by another site', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register($options, isCrossOrigin: true));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options))
        ->toThrow(InvalidPasskeyAttestationException::class, 'the ceremony ran in a page framed by another site.');

    expect(Passkey::query()->exists())->toBeFalse();
});

it('refuses an attestation made without verifying the user', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $credential = publicKeyCredential(new SoftwareAuthenticator()->register($options, isUserVerified: false));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options))
        ->toThrow(InvalidPasskeyAttestationException::class, 'User authentication required.');

    expect(Passkey::query()->exists())->toBeFalse();
});

it('refuses an assertion', function (): void {
    $user = User::factory()->create();
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);
    $authenticator = new SoftwareAuthenticator;
    $authenticator->register($options);
    $credential = publicKeyCredential($authenticator->authenticate(app(CreatePasskeyAuthenticationOptions::class)->handle()));

    expect(fn (): Passkey => app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', $credential), $options))
        ->toThrow(InvalidPasskeyAttestationException::class);

    expect(Passkey::query()->exists())->toBeFalse();
});
