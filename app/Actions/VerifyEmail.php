<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\EmailAlreadyVerifiedException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;

final readonly class VerifyEmail
{
    public function handle(User $user, CarbonImmutable $verifiedAt): void
    {
        if ($user->hasVerifiedEmail()) {
            throw EmailAlreadyVerifiedException::for($user);
        }

        $user->email_verified_at = $verifiedAt;
        $user->save();

        event(new Verified($user));
    }
}
