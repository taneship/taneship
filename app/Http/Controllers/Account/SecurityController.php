<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Data\PasskeyData;
use App\Http\PasskeyCeremony;
use App\Models\Passkey;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final class SecurityController
{
    public function edit(
        #[CurrentUser] User $user,
        CreatePasskeyRegistrationOptions $createPasskeyRegistrationOptions,
        PasskeyCeremony $passkeyCeremony,
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
            // Asked for when a registration starts.
            'passkeyOptions' => Inertia::optional(fn (): mixed => $passkeyCeremony->start(
                PasskeyCeremony::REGISTRATION,
                $createPasskeyRegistrationOptions->handle($user),
            )),
        ]);
    }
}
