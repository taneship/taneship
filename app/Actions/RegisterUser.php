<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\RegistrationData;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class RegisterUser
{
    public function handle(RegistrationData $registration): User
    {
        try {
            $user = User::query()->create([
                'name' => $registration->name,
                'email' => $registration->email,
                'password' => $registration->password,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another request took the address since it was validated: the unique index decides.
            throw EmailAlreadyTakenException::for($registration->email);
        }

        event(new Registered($user));

        return $user;
    }
}
