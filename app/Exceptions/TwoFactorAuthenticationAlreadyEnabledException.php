<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

final class TwoFactorAuthenticationAlreadyEnabledException extends RuntimeException
{
    public static function for(User $user): self
    {
        return new self("Two-factor authentication of user [{$user->id}] is already enabled.");
    }
}
