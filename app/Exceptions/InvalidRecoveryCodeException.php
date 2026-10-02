<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

final class InvalidRecoveryCodeException extends RuntimeException
{
    public static function for(User $user): self
    {
        return new self("The recovery code given for user [{$user->id}] is invalid or already used.");
    }
}
