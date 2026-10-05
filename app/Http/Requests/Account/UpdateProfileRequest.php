<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Data\ProfileData;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
        ];
    }

    public function toData(): ProfileData
    {
        return new ProfileData(
            name: $this->string('name')->value(),
            email: $this->string('email')->value(),
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
