<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\RegistrationData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

final class RegisterUserRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Unique|Password>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function toData(): RegistrationData
    {
        return new RegistrationData(
            name: $this->string('name')->value(),
            email: $this->string('email')->value(),
            password: $this->string('password')->value(),
        );
    }

    protected function prepareForValidation(): void
    {
        // The unique rule compares the address with stored ones, which are in lowercase.
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => Str::lower($email)]);
        }
    }
}
