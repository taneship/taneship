<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\RegenerateRecoveryCodes;
use App\Exceptions\TwoFactorAuthenticationNotEnabledException;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class RecoveryCodeController
{
    // A page left open in another tab still offers to regenerate the codes once two-factor authentication is off:
    // the answer leads back to the page, which shows the disabled state.
    public function store(#[CurrentUser] User $user, RegenerateRecoveryCodes $regenerateRecoveryCodes): RedirectResponse
    {
        try {
            $regenerateRecoveryCodes->handle($user);
        } catch (TwoFactorAuthenticationNotEnabledException) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('identity.two_factor_authentication.not_enabled')]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.two_factor_authentication.recovery_codes.regenerated')]);

        return back();
    }
}
