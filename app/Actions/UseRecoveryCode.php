<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidRecoveryCodeException;
use App\Models\User;
use SensitiveParameter;

final readonly class UseRecoveryCode
{
    public function handle(User $user, #[SensitiveParameter] string $recoveryCode): void
    {
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];

        // Compared in constant time: the answer's delay tells nothing of how much of a code matched.
        $usedCodes = array_filter($recoveryCodes, fn (mixed $code): bool => is_string($code) && hash_equals($code, $recoveryCode));

        if ($usedCodes === []) {
            throw InvalidRecoveryCodeException::for($user);
        }

        $user->two_factor_recovery_codes = array_values(array_diff_key($recoveryCodes, $usedCodes));
        $user->save();
    }
}
