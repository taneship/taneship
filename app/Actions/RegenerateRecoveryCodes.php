<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorAuthenticationNotEnabledException;
use App\Models\User;

final readonly class RegenerateRecoveryCodes
{
    public function handle(User $user): void
    {
        if (! $user->hasEnabledTwoFactorAuthentication()) {
            throw TwoFactorAuthenticationNotEnabledException::for($user);
        }

        $user->two_factor_recovery_codes = User::generateRecoveryCodes();
        $user->save();
    }
}
