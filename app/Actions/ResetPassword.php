<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\PasswordResetData;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Support\Str;

final readonly class ResetPassword
{
    public function __construct(private PasswordBroker $passwordBroker) {}

    public function handle(PasswordResetData $passwordReset): void
    {
        $status = $this->passwordBroker->reset(
            [
                'email' => $passwordReset->email,
                'password' => $passwordReset->password,
                'token' => $passwordReset->token,
            ],
            function (User $user, string $password): void {
                $user->password = $password;
                $user->setRememberToken(Str::random(60));
                $user->save();

                event(new PasswordReset($user));
            },
        );

        // The broker tells an address without an account from a wrong token: one exception answers both.
        if ($status !== PasswordBroker::PASSWORD_RESET) {
            throw InvalidPasswordResetTokenException::for($passwordReset->email);
        }
    }
}
