<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Passkey;
use App\Models\User;
use Cose\Algorithms;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Uri;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final readonly class CreatePasskeyRegistrationOptions
{
    public function handle(User $user): PublicKeyCredentialCreationOptions
    {
        return PublicKeyCredentialCreationOptions::create(
            rp: PublicKeyCredentialRpEntity::create(Config::string('app.name'), Uri::of(Config::string('app.url'))->host()),
            user: PublicKeyCredentialUserEntity::create($user->email, $this->userHandle($user), $user->name),
            challenge: random_bytes(32),
            pubKeyCredParams: [
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_ES256),
                PublicKeyCredentialParameters::createPk(Algorithms::COSE_ALGORITHM_RS256),
            ],
            authenticatorSelection: AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            excludeCredentials: $user->passkeys()->get()
                ->map(fn (Passkey $passkey): PublicKeyCredentialDescriptor => $passkey->credential->getPublicKeyCredentialDescriptor())
                ->all(),
        );
    }

    private function userHandle(User $user): string
    {
        // The authenticator keeps the handle and hands it to anyone holding the device: it must not reveal the user.
        // The prefix sets this signature apart from the others made with the application key, such as signed URLs.
        return hash_hmac('sha256', 'passkeys.user_handle:'.$user->id, Config::string('app.key'), true);
    }
}
