<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\RegisterPasskey;
use App\Exceptions\InvalidPasskeyAttestationException;
use App\Exceptions\PasskeyAlreadyRegisteredException;
use App\Http\Requests\Account\RegisterPasskeyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialCreationOptions;

final class PasskeyController
{
    public function store(
        RegisterPasskeyRequest $request,
        #[CurrentUser] User $user,
        RegisterPasskey $registerPasskey,
        SerializerInterface $serializer,
    ): RedirectResponse {
        $registration = $request->toData();

        // Pulled, so that a ceremony serves once: a replayed credential finds no options.
        $options = $request->session()->pull('passkeys.registration_options');

        if (! is_string($options)) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        try {
            $registerPasskey->handle($user, $registration, $serializer->deserialize($options, PublicKeyCredentialCreationOptions::class, 'json'));
        } catch (InvalidPasskeyAttestationException|PasskeyAlreadyRegisteredException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.passkeys.added')]);

        return back();
    }
}
