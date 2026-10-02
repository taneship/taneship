<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Actions\CreatePasskeyRegistrationOptions;
use App\Http\PasskeyCeremony;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

it('keeps the options in the session, and returns them without null values', function (): void {
    $options = app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create());

    $json = json_encode(app(PasskeyCeremony::class)->start(PasskeyCeremony::REGISTRATION, $options), JSON_THROW_ON_ERROR);

    expect($json)->toBe(session(PasskeyCeremony::REGISTRATION))
        ->and($json)->not->toContain(':null');
});

it('hands back the options it kept', function (): void {
    $options = app(CreatePasskeyRegistrationOptions::class)->handle(User::factory()->create());
    app(PasskeyCeremony::class)->start(PasskeyCeremony::REGISTRATION, $options);

    expect(app(PasskeyCeremony::class)->finish(PasskeyCeremony::REGISTRATION, PublicKeyCredentialCreationOptions::class))
        ->toEqual($options);
});

it('serves the options of a ceremony once', function (): void {
    app(PasskeyCeremony::class)->start(PasskeyCeremony::SIGN_IN, app(CreatePasskeyAuthenticationOptions::class)->handle());
    app(PasskeyCeremony::class)->finish(PasskeyCeremony::SIGN_IN, PublicKeyCredentialRequestOptions::class);

    expect(fn (): PublicKeyCredentialRequestOptions => app(PasskeyCeremony::class)->finish(PasskeyCeremony::SIGN_IN, PublicKeyCredentialRequestOptions::class))
        ->toThrow(ValidationException::class, trans('identity.passkeys.invalid'));
});

it('never serves a ceremony the options of another', function (): void {
    app(PasskeyCeremony::class)->start(PasskeyCeremony::SIGN_IN, app(CreatePasskeyAuthenticationOptions::class)->handle());

    expect(fn (): PublicKeyCredentialRequestOptions => app(PasskeyCeremony::class)->finish(PasskeyCeremony::CONFIRMATION, PublicKeyCredentialRequestOptions::class))
        ->toThrow(ValidationException::class, trans('identity.passkeys.invalid'));

    expect(session()->has(PasskeyCeremony::SIGN_IN))->toBeTrue();
});
