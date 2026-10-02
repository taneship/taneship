<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\VerifyPasskey;
use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\PasskeyOfAnotherUserException;
use App\Exceptions\UnknownPasskeyException;
use App\Http\Requests\Auth\ConfirmPasswordWithPasskeyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyConfirmationController
{
    public function store(
        ConfirmPasswordWithPasskeyRequest $request,
        #[CurrentUser] User $user,
        VerifyPasskey $verifyPasskey,
        SerializerInterface $serializer,
    ): RedirectResponse {
        $credential = $request->toData();

        // Pulled, so that a ceremony serves once: a replayed assertion finds no options.
        $options = $request->session()->pull('passkeys.confirmation_options');

        if (! is_string($options)) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        try {
            $verifyPasskey->handle($credential, $serializer->deserialize($options, PublicKeyCredentialRequestOptions::class, 'json'), $user);
        } catch (InvalidPasskeyAssertionException|UnknownPasskeyException|PasskeyOfAnotherUserException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard'));
    }
}
