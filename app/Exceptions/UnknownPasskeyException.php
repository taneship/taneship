<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class UnknownPasskeyException extends RuntimeException
{
    public static function for(string $credentialId): self
    {
        return new self("No passkey [{$credentialId}] is registered.");
    }
}
