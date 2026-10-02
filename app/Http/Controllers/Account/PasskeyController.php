<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\DeletePasskey;
use App\Actions\RegisterPasskey;
use App\Exceptions\InvalidPasskeyAttestationException;
use App\Exceptions\PasskeyAlreadyRegisteredException;
use App\Http\PasskeyCeremony;
use App\Http\Requests\Account\RegisterPasskeyRequest;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Webauthn\PublicKeyCredentialCreationOptions;

final class PasskeyController
{
    public function store(
        RegisterPasskeyRequest $request,
        #[CurrentUser] User $user,
        RegisterPasskey $registerPasskey,
        PasskeyCeremony $passkeyCeremony,
    ): RedirectResponse {
        $registration = $request->toData();
        $options = $passkeyCeremony->finish(PasskeyCeremony::REGISTRATION, PublicKeyCredentialCreationOptions::class);

        try {
            $registerPasskey->handle($user, $registration, $options);
        } catch (InvalidPasskeyAttestationException|PasskeyAlreadyRegisteredException) {
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.passkeys.added')]);

        return back();
    }

    public function destroy(Passkey $passkey, DeletePasskey $deletePasskey): RedirectResponse
    {
        $deletePasskey->handle($passkey);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.passkeys.removed')]);

        return back();
    }
}
