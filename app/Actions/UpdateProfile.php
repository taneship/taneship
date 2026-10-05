<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ProfileData;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;

final readonly class UpdateProfile
{
    // Laravel's own broker, not its contract, which cannot delete a token.
    public function __construct(private PasswordBroker $passwordBroker) {}

    public function handle(User $user, ProfileData $profile): void
    {
        // The broker finds a token by the address of the user it is given: this copy keeps the former one.
        $userBeforeUpdate = clone $user;

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
            // A token is kept by address: left behind, a link sent to the former address would reset
            // the password of an account opened later with it.
            $this->passwordBroker->deleteToken($userBeforeUpdate);

            $user->sendEmailVerificationNotification();
        }
    }
}
