<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

function bootAppServiceProviderIn(string $environment): void
{
    app()->instance('env', $environment);

    app()->getProvider(AppServiceProvider::class)?->boot();
}

it('makes models strict outside production', function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

it('does not make models strict in production', function (): void {
    bootAppServiceProviderIn('production');

    expect(Model::preventsLazyLoading())->toBeFalse()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeFalse()
        ->and(Model::preventsAccessingMissingAttributes())->toBeFalse();
});

it('uses immutable dates', function (): void {
    expect(now())->toBeInstanceOf(CarbonImmutable::class);
});

it('prohibits destructive database commands in production', function (string $command): void {
    bootAppServiceProviderIn('production');

    expect($this->artisan($command, ['--force' => true]))->toBe(1);
})->with(['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']);

it('requires passwords of 12 characters, with no composition rule and no breach check', function (string $environment): void {
    bootAppServiceProviderIn($environment);

    expect(Password::defaults()->appliedRules())->toMatchArray([
        'min' => 12,
        'mixedCase' => false,
        'letters' => false,
        'numbers' => false,
        'symbols' => false,
        'uncompromised' => false,
    ]);
})->with(['testing', 'production']);

it('binds one serializer for webauthn data', function (): void {
    expect(app(SerializerInterface::class))->toBe(app(SerializerInterface::class));
});

it('reads back the ceremony options it serializes', function (): void {
    $options = PublicKeyCredentialCreationOptions::create(
        rp: PublicKeyCredentialRpEntity::create('Taneship', 'localhost'),
        user: PublicKeyCredentialUserEntity::create('jane@example.com', random_bytes(32), 'Jane Doe'),
        challenge: random_bytes(32),
        pubKeyCredParams: [PublicKeyCredentialParameters::createPk(-7)],
        authenticatorSelection: AuthenticatorSelectionCriteria::create(userVerification: 'required', residentKey: 'required'),
        attestation: 'none',
        excludeCredentials: [PublicKeyCredentialDescriptor::create('public-key', random_bytes(32))],
    );

    $serializer = app(SerializerInterface::class);

    expect($serializer->deserialize($serializer->serialize($options, 'json'), PublicKeyCredentialCreationOptions::class, 'json'))
        ->toEqual($options);
});
