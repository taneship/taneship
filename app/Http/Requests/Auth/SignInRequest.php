<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\SignInData;
use Illuminate\Foundation\Http\FormRequest;

final class SignInRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function toData(): SignInData
    {
        return new SignInData(
            email: $this->string('email')->lower()->value(),
            password: $this->string('password')->value(),
            remember: $this->boolean('remember'),
        );
    }
}
