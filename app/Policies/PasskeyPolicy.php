<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Passkey;
use App\Models\User;

final class PasskeyPolicy
{
    public function delete(User $user, Passkey $passkey): bool
    {
        return $passkey->user()->is($user);
    }
}
