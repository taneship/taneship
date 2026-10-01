<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Models\Passkey;
use App\Models\User;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;

it('names the relying party after the application and the host of its url', function (): void {
    config(['app.name' => 'Acme', 'app.url' => 'https://acme.example:8443']);

    $options = app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create());

    expect($options->rp->id)->toBe('acme.example')
        ->and($options->rp->name)->toBe('Acme');
});

it('names the user by their address and their name', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);

    expect($options->user->name)->toBe('jane@example.com')
        ->and($options->user->displayName)->toBe('Jane Doe');
});

it('gives each user a stable handle, derived with the application key', function (): void {
    [$jane, $john] = User::factory()->count(2)->create();
    $createOptions = app(CreatePasskeyRegistrationOptions::class);

    $handle = $createOptions->handle($jane)->user->id;

    expect(strlen($handle))->toBe(32)
        ->and($createOptions->handle($jane)->user->id)->toBe($handle)
        ->and($createOptions->handle($john)->user->id)->not->toBe($handle);

    config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

    expect($createOptions->handle($jane)->user->id)->not->toBe($handle);
});

it('asks for a discoverable credential, user verification and no attestation', function (): void {
    $options = app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create());

    expect($options->authenticatorSelection?->residentKey)->toBe('required')
        ->and($options->authenticatorSelection?->userVerification)->toBe('required')
        ->and($options->authenticatorSelection?->authenticatorAttachment)->toBeNull()
        ->and($options->attestation)->toBe('none');
});

it('accepts ES256 and RS256 keys', function (): void {
    $options = app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create());

    expect(array_map(fn (PublicKeyCredentialParameters $parameters): int => $parameters->alg, $options->pubKeyCredParams))
        ->toBe([-7, -257]);
});

it('draws a new challenge each time', function (): void {
    $user = User::factory()->create();
    $createOptions = app(CreatePasskeyRegistrationOptions::class);

    $challenge = $createOptions->handle($user)->challenge;

    expect(strlen($challenge))->toBe(32)
        ->and($createOptions->handle($user)->challenge)->not->toBe($challenge);
});

it('excludes the passkeys the user already has', function (): void {
    $user = User::factory()->create();
    $passkeys = Passkey::factory()->for($user)->count(2)->create();
    Passkey::factory()->create();

    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);

    expect(array_map(fn (PublicKeyCredentialDescriptor $descriptor): string => $descriptor->id, $options->excludeCredentials))
        ->toEqualCanonicalizing($passkeys->map(fn (Passkey $passkey): string => $passkey->credential->publicKeyCredentialId)->all());
});
