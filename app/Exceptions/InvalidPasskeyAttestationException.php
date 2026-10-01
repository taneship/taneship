<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidPasskeyAttestationException extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self("The passkey attestation is invalid: {$reason}", previous: $previous);
    }
}
