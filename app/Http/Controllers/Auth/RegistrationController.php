<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterUser;
use App\Exceptions\EmailAlreadyTakenException;
use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class RegistrationController
{
    public function create(): Response
    {
        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]);
    }

    public function store(RegisterUserRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        try {
            $user = $registerUser->handle($request->toData());
        } catch (EmailAlreadyTakenException) {
            // The form request found the address free, and another request took it since: the answer is the rule's.
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        Auth::login($user);

        $request->session()->regenerate();

        return to_route('dashboard');
    }
}
