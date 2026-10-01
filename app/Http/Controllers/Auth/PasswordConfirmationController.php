<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordConfirmationController
{
    public function create(): Response
    {
        return Inertia::render('auth/confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        // No action takes the password, so no form request hands one over: the rule is checked here.
        $request->validate(['password' => ['required', 'string', 'current_password']]);

        $request->session()->passwordConfirmed();

        return redirect()->intended(route('dashboard'));
    }
}
