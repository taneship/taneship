<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\VerifyPasskey;
use App\Exceptions\InvalidPasskeyAssertionException;
use App\Exceptions\PasskeyOfAnotherUserException;
use App\Exceptions\UnknownPasskeyException;
use App\Http\PasskeyCeremony;
use App\Http\Requests\Auth\ConfirmPasswordWithPasskeyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyConfirmationController
{
    public function store(
        ConfirmPasswordWithPasskeyRequest $request,
        #[CurrentUser] User $user,
        VerifyPasskey $verifyPasskey,
        PasskeyCeremony $passkeyCeremony,
    ): RedirectResponse {
        $credential = $request->toData();
        $options = $passkeyCeremony->finish(PasskeyCeremony::CONFIRMATION, PublicKeyCredentialRequestOptions::class);

        try {
            $verifyPasskey->handle($credential, $options, $user);
        } catch (InvalidPasskeyAssertionException|UnknownPasskeyException|PasskeyOfAnotherUserException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard'));
    }
}
