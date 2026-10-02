<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\InvalidTwoFactorCodeException;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;
use PragmaRX\Google2FA\Google2FA;
use SensitiveParameter;

final readonly class VerifyTwoFactorCode
{
    // One step either way, for a phone whose clock drifts.
    private const int WINDOW = 1;

    public function __construct(private Google2FA $google2fa, private Repository $cache) {}

    public function handle(User $user, #[SensitiveParameter] string $code): void
    {
        if ($user->two_factor_secret === null) {
            throw InvalidTwoFactorCodeException::for($user);
        }

        $key = "two_factor.last_step.{$user->id}";
        $lastStep = $this->cache->get($key);

        // google2fa reads its own clock, which ignores the time a test sets.
        $step = intdiv(now()->getTimestamp(), $this->google2fa->getKeyRegeneration());

        // Given no last step, google2fa answers true instead of the step it matched: a step before the window stands in.
        $acceptedStep = $this->google2fa->verifyKeyNewer(
            $user->two_factor_secret,
            $code,
            is_int($lastStep) ? $lastStep : $step - self::WINDOW - 1,
            self::WINDOW,
            $step,
        );

        if (! is_int($acceptedStep)) {
            throw InvalidTwoFactorCodeException::for($user);
        }

        // An accepted step stays within the window for three steps at most.
        $this->cache->put($key, $acceptedStep, (2 * self::WINDOW + 1) * $this->google2fa->getKeyRegeneration());
    }
}
