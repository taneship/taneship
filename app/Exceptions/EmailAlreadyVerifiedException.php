<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\User;
use RuntimeException;

final class EmailAlreadyVerifiedException extends RuntimeException
{
    public static function for(User $user): self
    {
        return new self("The email address of user [{$user->id}] is already verified.");
    }
}
