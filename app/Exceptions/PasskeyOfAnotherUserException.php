<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Passkey;
use App\Models\User;
use RuntimeException;

final class PasskeyOfAnotherUserException extends RuntimeException
{
    public static function for(Passkey $passkey, User $user): self
    {
        return new self("The passkey [{$passkey->credential_id}] does not belong to user [{$user->id}].");
    }
}
