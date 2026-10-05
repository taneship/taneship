<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ProfileData;
use App\Models\User;

final readonly class UpdateProfile
{
    public function handle(User $user, ProfileData $profile): void
    {
        $user->fill([
            'name' => $profile->name,
            'email' => $profile->email,
        ]);

        // The model lowercases the address: a change of case alone leaves it clean.
        $hasNewEmail = $user->isDirty('email');

        if ($hasNewEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($hasNewEmail) {
            $user->sendEmailVerificationNotification();
        }
    }
}
