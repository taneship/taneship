<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\SendPasswordResetLink;
use App\Http\Requests\Auth\SendPasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordResetLinkController
{
    public function create(): Response
    {
        return Inertia::render('auth/forgot-password');
    }

    public function store(SendPasswordResetLinkRequest $request, SendPasswordResetLink $sendPasswordResetLink): RedirectResponse
    {
        $sendPasswordResetLink->handle($request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.password.sent')]);

        return back();
    }
}
