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

        try {
            $record = $this->attestationValidator->check($registration->credential->response, $options, (string) $options->rp->id);
        } catch (Throwable $exception) {
            // A forged attestation fails in webauthn-lib or in the CBOR and COSE libraries it reads it with.
            throw InvalidPasskeyAttestationException::because($exception->getMessage(), $exception);
        }

        $passkey = $user->passkeys()->make();
        $passkey->name = $registration->name;
        $passkey->credential = $record;

        if (Passkey::query()->where('credential_id', $passkey->credential_id)->exists()) {
            throw PasskeyAlreadyRegisteredException::for($passkey->credential_id);
        }

        $passkey->save();

        return $passkey;
    }
}
