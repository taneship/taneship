<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class InvalidPasswordResetTokenException extends RuntimeException
{
    public static function for(string $email): self
    {
        return new self("No valid password reset token matches the address [{$email}].");
    }
}
