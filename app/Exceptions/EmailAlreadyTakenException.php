<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class EmailAlreadyTakenException extends RuntimeException
{
    public static function for(string $email): self
    {
        return new self("The email address [{$email}] is already taken.");
    }
}
