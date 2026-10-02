<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Data\PasskeySignInData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use Webauthn\PublicKeyCredential;

final class SignInWithPasskeyRequest extends FormRequest
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

    public function toData(): PasskeySignInData
    {
        try {
            return new PasskeySignInData(
                credential: app(SerializerInterface::class)->deserialize($this->string('credential')->value(), PublicKeyCredential::class, 'json'),
                remember: $this->boolean('remember'),
            );
        } catch (Throwable) {
            // webauthn-lib fails in many ways on what it cannot read, down to a type error: each is a failed ceremony.
            throw ValidationException::withMessages(['credential' => __('identity.passkeys.invalid')]);
        }
    }
}
