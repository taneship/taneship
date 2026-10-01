<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\VerifyEmail;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class EmailVerificationController
{
    public function create(#[CurrentUser] User $user): Response|RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        return Inertia::render('auth/verify-email');
    }

    public function store(#[CurrentUser] User $user, VerifyEmail $verifyEmail, string $id, string $hash): RedirectResponse
    {
        // The hash ties the link to the address it was sent to: once the address changes, the link fails.
        abort_unless(
            hash_equals((string) $user->id, $id)
                && hash_equals(hash('sha256', $user->getEmailForVerification()), $hash),
            403,
        );

        if ($user->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $verifyEmail->handle($user, CarbonImmutable::now());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.verification.verified')]);

        return to_route('dashboard');
    }
}
