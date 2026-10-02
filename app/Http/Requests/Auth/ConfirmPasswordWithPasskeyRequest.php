<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\PublicKeyCredential;

final class ConfirmPasswordWithPasskeyRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'string'],
        ];
    }

    public function toData(): PublicKeyCredential
    {
        try {
            return app(SerializerInterface::class)->deserialize($this->string('credential')->value(), PublicKeyCredential::class, 'json');
        } catch (Throwable) {
            // webauthn-lib fails in many ways on what it cannot read, down to a type error: each is a failed ceremony.
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }
    }
}
