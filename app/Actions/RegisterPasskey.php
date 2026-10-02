<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\PasskeyRegistrationData;
use App\Exceptions\InvalidPasskeyAttestationException;
use App\Exceptions\PasskeyAlreadyRegisteredException;
use App\Models\Passkey;
use App\Models\User;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialCreationOptions;

final readonly class RegisterPasskey
{
    public function __construct(private AuthenticatorAttestationResponseValidator $attestationValidator) {}

    public function handle(User $user, PasskeyRegistrationData $registration, PublicKeyCredentialCreationOptions $options): Passkey
    {
        if (! $registration->credential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskeyAttestationException::because('the credential answers no registration.');
        }

        // webauthn-lib lets both pass: the client data of an authentication, and a page framed by another site.
        if ($registration->credential->response->clientDataJSON->type !== 'webauthn.create') {
            throw InvalidPasskeyAttestationException::because('the client data answer no registration.');
        }

        if ($registration->credential->response->clientDataJSON->crossOrigin) {
            throw InvalidPasskeyAttestationException::because('the ceremony ran in a page framed by another site.');
        }

        try {
            $record = $this->attestationValidator->check($registration->credential->response, $options, (string) $options->rp->id);
        } catch (Throwable $exception) {
            // A forged attestation fails in webauthn-lib or in the CBOR and COSE libraries it reads it with.
            throw InvalidPasskeyAttestationException::because($exception->getMessage(), $exception);
        }

        $passkey = $user->passkeys()->make();
        $passkey->name = $registration->name;
        $passkey->credential = $record;

        // WebAuthn allows ids of 1023 bytes, which passkeys stay far from: the column holds 255 characters.
        if (strlen($passkey->credential_id) > 255) {
            throw InvalidPasskeyAttestationException::because('the credential id exceeds 255 characters.');
        }

        if (Passkey::query()->where('credential_id', $passkey->credential_id)->exists()) {
            throw PasskeyAlreadyRegisteredException::for($passkey->credential_id);
        }

        $passkey->save();

        return $passkey;
    }
}
