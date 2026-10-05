<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;

final readonly class DeleteUser
{
    // Laravel's own broker, not its contract, which cannot delete a token.
    public function __construct(private PasswordBroker $passwordBroker) {}

    public function handle(User $user): void
    {
        // A token is kept by address: left behind, a link sent before the deletion would reset
        // the password of an account opened later with the same address.
        $this->passwordBroker->deleteToken($user);

        $user->delete();
    }
}
