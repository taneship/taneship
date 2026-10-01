<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Data\PasskeyData;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final class SecurityController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('account/security', [
            // Relative times, written on the server: a date formatted in the browser
            // would depend on its time zone, and differ from the server render.
            'passkeys' => $user->passkeys()->latest()->get()->map(fn (Passkey $passkey): PasskeyData => new PasskeyData(
                id: $passkey->id,
                name: $passkey->name,
                holder: $passkey->holder,
                added: $passkey->created_at->diffForHumans(),
                lastUsed: $passkey->last_used_at?->diffForHumans(),
            )),
        ]);
    }
}
