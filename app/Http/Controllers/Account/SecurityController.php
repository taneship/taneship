<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Data\PasskeyData;
use App\Models\Passkey;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class SecurityController
{
    public function edit(
        Request $request,
        #[CurrentUser] User $user,
        CreatePasskeyRegistrationOptions $createPasskeyRegistrationOptions,
        SerializerInterface $serializer,
    ): Response {
        return Inertia::render('account/security', [
            // Relative times, written on the server: a date formatted in the browser
            // would depend on its time zone, and differ from the server render.
            'passkeys' => $user->passkeys()->latest()->get()->map(fn (Passkey $passkey): PasskeyData => new PasskeyData(
                id: $passkey->id,
                name: $passkey->name,
                holder: $passkey->holder,
                added: $passkey->created_at->diffForHumans(['options' => CarbonInterface::JUST_NOW]),
                lastUsed: $passkey->last_used_at?->diffForHumans(['options' => CarbonInterface::JUST_NOW]),
            )),
            // Asked for when a registration starts. The session keeps them, challenge included, for the credential.
            'passkeyOptions' => Inertia::optional(function () use ($request, $user, $createPasskeyRegistrationOptions, $serializer): mixed {
                // Without null values: the browser refuses a null where WebAuthn expects a value, such as authenticatorAttachment.
                $options = $serializer->serialize($createPasskeyRegistrationOptions->handle($user), 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);

                $request->session()->put('passkeys.registration_options', $options);

                return json_decode($options, flags: JSON_THROW_ON_ERROR);
            }),
        ]);
    }
}
