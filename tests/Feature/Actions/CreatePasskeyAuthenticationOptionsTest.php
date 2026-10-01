<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Models\Passkey;
use App\Models\User;
use Webauthn\PublicKeyCredentialDescriptor;

it('names the relying party by the host of the application url', function (): void {
    config(['app.url' => 'https://acme.example:8443']);

    expect(app(CreatePasskeyAuthenticationOptions::class)->handle()->rpId)->toBe('acme.example');
});

it('requires user verification', function (): void {
    expect(app(CreatePasskeyAuthenticationOptions::class)->handle()->userVerification)->toBe('required');
});

it('draws a new challenge each time', function (): void {
    $createOptions = app(CreatePasskeyAuthenticationOptions::class);

    $challenge = $createOptions->handle()->challenge;

    expect(strlen($challenge))->toBe(32)
        ->and($createOptions->handle()->challenge)->not->toBe($challenge);
});

it('allows any passkey of the site when no user is given', function (): void {
    Passkey::factory()->create();

    expect(app(CreatePasskeyAuthenticationOptions::class)->handle()->allowCredentials)->toBe([]);
});

it('allows only the passkeys of the given user', function (): void {
    $user = User::factory()->create();
    $passkeys = Passkey::factory()->for($user)->count(2)->create();
    Passkey::factory()->create();

    $options = app(CreatePasskeyAuthenticationOptions::class)->handle($user);

    expect(array_map(fn (PublicKeyCredentialDescriptor $descriptor): string => $descriptor->id, $options->allowCredentials))
        ->toEqualCanonicalizing($passkeys->map(fn (Passkey $passkey): string => $passkey->credential->publicKeyCredentialId)->all());
});
