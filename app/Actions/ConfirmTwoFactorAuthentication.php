<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TwoFactorAuthenticationAlreadyEnabledException;
use App\Models\User;
use SensitiveParameter;

final readonly class ConfirmTwoFactorAuthentication
{
    public function __construct(private VerifyTwoFactorCode $verifyTwoFactorCode) {}

    public function handle(User $user, #[SensitiveParameter] string $code): void
    {
        if ($user->hasEnabledTwoFactorAuthentication()) {
            throw TwoFactorAuthenticationAlreadyEnabledException::for($user);
        }

        $this->verifyTwoFactorCode->handle($user, $code);

        $user->two_factor_confirmed_at = now();
        $user->save();
    }
}
