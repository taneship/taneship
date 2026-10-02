<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class PasswordConfirmationController
{
    public function create(
        Request $request,
        #[CurrentUser] User $user,
        CreatePasskeyAuthenticationOptions $createPasskeyAuthenticationOptions,
        SerializerInterface $serializer,
    ): Response {
        return Inertia::render('auth/confirm-password', [
            'hasPasskeys' => $user->passkeys()->exists(),
            // Asked for when a ceremony starts. The session keeps them, challenge included, for the credential.
            'passkeyOptions' => Inertia::optional(function () use ($request, $user, $createPasskeyAuthenticationOptions, $serializer): mixed {
                // Without null values: the browser refuses a null where WebAuthn expects a value.
                $options = $serializer->serialize($createPasskeyAuthenticationOptions->handle($user), 'json', [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);

                $request->session()->put('passkeys.confirmation_options', $options);

                return json_decode($options, flags: JSON_THROW_ON_ERROR);
            }),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // No action takes the password, so no form request hands one over: the rule is checked here.
        $request->validate(['password' => ['required', 'string', 'current_password']]);

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard'));
    }
}
