<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ProfileData;
use App\Models\User;

final readonly class UpdateProfile
{
    public function handle(User $user, ProfileData $profile): void
    {
        $user->update([
            'name' => $profile->name,
            'email' => $profile->email,
        ]);
    }
}
