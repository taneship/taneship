<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class PassTwoFactorChallengeRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'required_without:recovery_code', 'string', 'digits:6'],
            'recovery_code' => ['nullable', 'required_without:code', 'string', 'max:255'],
        ];
    }

    public function usesRecoveryCode(): bool
    {
        return $this->filled('recovery_code');
    }

    public function toData(): string
    {
        return $this->string($this->usesRecoveryCode() ? 'recovery_code' : 'code')->value();
    }
}
