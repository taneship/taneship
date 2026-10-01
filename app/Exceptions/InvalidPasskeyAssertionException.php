<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidPasskeyAssertionException extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self("The passkey assertion is invalid: {$reason}", previous: $previous);
    }
}
