<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\RegistrationData;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

final readonly class RegisterUser
{
    public function handle(RegistrationData $registration): User
    {
        $user = User::query()->create([
            'name' => $registration->name,
            'email' => $registration->email,
            'password' => $registration->password,
        ]);

        event(new Registered($user));

        return $user;
    }
}
