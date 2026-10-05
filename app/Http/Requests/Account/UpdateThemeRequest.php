<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use App\Enums\Theme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class UpdateThemeRequest extends FormRequest
{
    /**
     * @return array<string, list<string|Enum>>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::enum(Theme::class)],
        ];
    }

    public function toData(): Theme
    {
        return Theme::from($this->string('theme')->value());
    }
}
