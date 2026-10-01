<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Contracts\Auth\PasswordBroker;

final readonly class SendPasswordResetLink
{
    public function __construct(private PasswordBroker $passwordBroker) {}

    public function handle(string $email): void
    {
        // The broker's status stays here: an address without an account, or a link asked for less than a
        // minute ago, gets the answer of a sent link, so the answer reveals no account.
        $this->passwordBroker->sendResetLink(['email' => $email]);
    }
}
