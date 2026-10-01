<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\ResetPassword;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class PasswordResetController
{
    public function create(Request $request, string $token): Response
    {
        $email = $request->query('email');

        return Inertia::render('auth/reset-password', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetPassword $resetPassword): RedirectResponse
    {
        try {
            $resetPassword->handle($request->toData());
        } catch (InvalidPasswordResetTokenException) {
            throw ValidationException::withMessages(['email' => __('passwords.token')]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('identity.password.reset')]);

        return to_route('login');
    }
}
