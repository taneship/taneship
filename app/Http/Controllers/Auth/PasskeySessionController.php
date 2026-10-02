<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\VerifyPasskey;
use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\UnknownPasskeyException;
use App\Http\PasskeyCeremony;
use App\Http\Requests\Auth\SignInWithPasskeyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeySessionController
{
    public function store(
        SignInWithPasskeyRequest $request,
        VerifyPasskey $verifyPasskey,
        PasskeyCeremony $passkeyCeremony,
    ): RedirectResponse {
        $signIn = $request->toData();
        $options = $passkeyCeremony->finish(PasskeyCeremony::SIGN_IN, PublicKeyCredentialRequestOptions::class);

        try {
            $passkey = $verifyPasskey->handle($signIn->credential, $options);
        } catch (InvalidPasskeyAssertionException|UnknownPasskeyException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        Auth::login($passkey->user, $signIn->remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
