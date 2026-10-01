<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\PasswordResetData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Password>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): PasswordResetData
    {
        return new PasswordResetData(
            token: $this->string('token')->value(),
            email: $this->string('email')->lower()->value(),
            password: $this->string('password')->value(),
        );
    }
}
