<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Contracts\Cache\Repository;

final readonly class DisableTwoFactorAuthentication
{
    public function __construct(private Repository $cache) {}

    public function handle(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        // The last accepted step belongs to the discarded secret: kept, it would refuse the next secret's code of that step.
        $this->cache->forget(VerifyTwoFactorCode::LAST_STEP_KEY_PREFIX.$user->id);
    }
}
