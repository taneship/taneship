<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class EmailVerificationNotificationController
{
    public function store(#[CurrentUser] User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.verification.sent')]);

        return back();
    }
}
