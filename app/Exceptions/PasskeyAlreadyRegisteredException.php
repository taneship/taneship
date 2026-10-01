<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class PasskeyAlreadyRegisteredException extends RuntimeException
{
    public static function for(string $credentialId): self
    {
        return new self("The passkey [{$credentialId}] is already registered.");
    }
}
