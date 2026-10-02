<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Http\PasskeyCeremony;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordConfirmationController
{
    public function create(
        #[CurrentUser] User $user,
        CreatePasskeyAuthenticationOptions $createPasskeyAuthenticationOptions,
        PasskeyCeremony $passkeyCeremony,
    ): Response {
        return Inertia::render('auth/confirm-password', [
            'hasPasskeys' => $user->passkeys()->exists(),
            // Asked for when a ceremony starts.
            'passkeyOptions' => Inertia::optional(fn (): mixed => $passkeyCeremony->start(
                PasskeyCeremony::CONFIRMATION,
                $createPasskeyAuthenticationOptions->handle($user),
            )),
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
