<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorAuthenticationAlreadyEnabledException;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

final readonly class EnableTwoFactorAuthentication
{
    public function __construct(private Google2FA $google2fa) {}

    public function handle(User $user): void
    {
        if ($user->hasEnabledTwoFactorAuthentication()) {
            throw TwoFactorAuthenticationAlreadyEnabledException::for($user);
        }

        // 32 base32 characters make 160 bits, the length RFC 4226 recommends.
        $user->two_factor_secret = $this->google2fa->generateSecretKey(32);
        $user->two_factor_recovery_codes = User::generateRecoveryCodes();
        $user->save();
    }
}
