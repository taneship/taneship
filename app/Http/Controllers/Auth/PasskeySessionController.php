<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\VerifyPasskey;
use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\UnknownPasskeyException;
use App\Http\Requests\Auth\SignInWithPasskeyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeySessionController
{
    public function store(
        SignInWithPasskeyRequest $request,
        VerifyPasskey $verifyPasskey,
        SerializerInterface $serializer,
    ): RedirectResponse {
        $signIn = $request->toData();

        // Pulled, so that a ceremony serves once: a replayed assertion finds no options.
        $options = $request->session()->pull('passkeys.authentication_options');

        if (! is_string($options)) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        try {
            $passkey = $verifyPasskey->handle($signIn->credential, $serializer->deserialize($options, PublicKeyCredentialRequestOptions::class, 'json'));
        } catch (InvalidPasskeyAssertionException|UnknownPasskeyException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        Auth::login($passkey->user, $signIn->remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
