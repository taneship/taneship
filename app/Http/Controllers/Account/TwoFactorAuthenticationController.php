<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\ConfirmTwoFactorAuthentication;
use App\Actions\DisableTwoFactorAuthentication;
use App\Actions\EnableTwoFactorAuthentication;
use App\Exceptions\InvalidTwoFactorCodeException;
use App\Exceptions\TwoFactorAuthenticationAlreadyEnabledException;
use App\Http\Requests\Account\ConfirmTwoFactorAuthenticationRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

// A page left open in another tab still offers the setup once two-factor authentication is enabled:
// the answer leads back to the page, which shows the enabled state.
final class TwoFactorAuthenticationController
{
    public function store(#[CurrentUser] User $user, EnableTwoFactorAuthentication $enableTwoFactorAuthentication): RedirectResponse
    {
        try {
            $enableTwoFactorAuthentication->handle($user);
        } catch (TwoFactorAuthenticationAlreadyEnabledException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('identity.two_factor_authentication.already_enabled')]);
        }

        return back();
    }

    public function update(
        ConfirmTwoFactorAuthenticationRequest $request,
        #[CurrentUser] User $user,
        ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication,
    ): RedirectResponse {
        try {
            $confirmTwoFactorAuthentication->handle($user, $request->toData());
        } catch (InvalidTwoFactorCodeException) {
            throw ValidationException::withMessages(['code' => __('identity.two_factor_authentication.invalid_code')]);
        } catch (TwoFactorAuthenticationAlreadyEnabledException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('identity.two_factor_authentication.already_enabled')]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.two_factor_authentication.enabled')]);

        return back();
    }

    // Canceling a pending setup and turning two-factor authentication off discard the same columns: only the toast tells them apart.
    public function destroy(#[CurrentUser] User $user, DisableTwoFactorAuthentication $disableTwoFactorAuthentication): RedirectResponse
    {
        $isPending = $user->two_factor_secret !== null && ! $user->hasEnabledTwoFactorAuthentication();

        $disableTwoFactorAuthentication->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isPending ? __('identity.two_factor_authentication.canceled') : __('identity.two_factor_authentication.disabled'),
        ]);

        return back();
    }
}
