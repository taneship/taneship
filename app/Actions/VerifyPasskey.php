<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\PasskeyOfAnotherUserException;
use App\Exceptions\UnknownPasskeyException;
use App\Models\Passkey;
use App\Models\User;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

final readonly class VerifyPasskey
{
    public function __construct(private AuthenticatorAssertionResponseValidator $assertionValidator) {}

    public function handle(PublicKeyCredential $credential, PublicKeyCredentialRequestOptions $options, ?User $user = null): Passkey
    {
        if (! $credential->response instanceof AuthenticatorAssertionResponse) {
            throw InvalidPasskeyAssertionException::because('the credential answers no authentication.');
        }

        // In base64url, as the passkey stores it.
        $credentialId = rtrim(strtr(base64_encode($credential->rawId), '+/', '-_'), '=');

        $passkey = Passkey::query()->firstWhere('credential_id', $credentialId)
            ?? throw UnknownPasskeyException::for($credentialId);

        if ($user instanceof User && $passkey->user()->isNot($user)) {
            throw PasskeyOfAnotherUserException::for($passkey, $user);
        }

        try {
            $record = $this->assertionValidator->check(
                $passkey->credential,
                $credential->response,
                $options,
                (string) $options->rpId,
                // A known user already owns the passkey; without one, the response must name the passkey's owner.
                $user instanceof User ? $passkey->credential->userHandle : null,
            );
        } catch (Throwable $exception) {
            // A forged assertion fails in webauthn-lib or in the CBOR and COSE libraries it reads it with.
            throw InvalidPasskeyAssertionException::because($exception->getMessage(), $exception);
        }

        $passkey->credential = $record;
        $passkey->last_used_at = now();
        $passkey->save();

        return $passkey;
    }
}
