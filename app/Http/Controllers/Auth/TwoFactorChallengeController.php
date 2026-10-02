<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\UseRecoveryCode;
use App\Actions\VerifyTwoFactorCode;
use App\Exceptions\InvalidRecoveryCodeException;
use App\Exceptions\InvalidTwoFactorCodeException;
use App\Http\Requests\Auth\PassTwoFactorChallengeRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class TwoFactorChallengeController
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->pendingUser($request) instanceof User) {
            return to_route('login');
        }

        return Inertia::render('auth/two-factor-challenge');
    }

    public function store(
        PassTwoFactorChallengeRequest $request,
        VerifyTwoFactorCode $verifyTwoFactorCode,
        UseRecoveryCode $useRecoveryCode,
    ): RedirectResponse {
        $user = $this->pendingUser($request);

        if (! $user instanceof User) {
            return to_route('login');
        }

        // Counted per user: signing in with the password again starts no new count.
        $throttleKey = 'two-factor-challenge.store|'.$user->id;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                $request->usesRecoveryCode() ? 'recovery_code' : 'code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        try {
            if ($request->usesRecoveryCode()) {
                $useRecoveryCode->handle($user, $request->toData());
            } else {
                $verifyTwoFactorCode->handle($user, $request->toData());
            }
        } catch (InvalidTwoFactorCodeException) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['code' => __('identity.two_factor_authentication.invalid_code')]);
        } catch (InvalidRecoveryCodeException) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['recovery_code' => __('identity.two_factor_authentication.recovery_codes.invalid')]);
        }

        RateLimiter::clear($throttleKey);

        $remember = $request->session()->get('pending_sign_in.remember') === true;
        $request->session()->forget('pending_sign_in');

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // Kept by sign-in once the password is right. A user deleted, or whose two-factor authentication
    // was turned off since, leaves no challenge to pass.
    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('pending_sign_in.user_id');
        $user = is_int($userId) ? User::query()->find($userId) : null;

        return $user?->hasEnabledTwoFactorAuthentication() === true ? $user : null;
    }
}
