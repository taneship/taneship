<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Uri;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;

final readonly class CreatePasskeyAuthenticationOptions
{
    public function handle(?User $user = null): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            challenge: random_bytes(32),
            rpId: Uri::of(Config::string('app.url'))->host(),
            // Without a user, the browser offers the passkeys it holds for this site.
            allowCredentials: $user?->passkeys()->get()
                ->map(fn (Passkey $passkey): PublicKeyCredentialDescriptor => $passkey->credential->getPublicKeyCredentialDescriptor())
                ->all() ?? [],
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
        );
    }
}
