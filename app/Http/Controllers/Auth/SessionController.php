<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\CreatePasskeyAuthenticationOptions;
use App\Http\PasskeyCeremony;
use App\Http\Requests\Auth\SignInRequest;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class SessionController
{
    public function create(
        CreatePasskeyAuthenticationOptions $createPasskeyAuthenticationOptions,
        PasskeyCeremony $passkeyCeremony,
    ): Response {
        return Inertia::render('auth/login', [
            // Asked for when a ceremony starts.
            'passkeyOptions' => Inertia::optional(fn (): mixed => $passkeyCeremony->start(
                PasskeyCeremony::SIGN_IN,
                $createPasskeyAuthenticationOptions->handle(),
            )),
        ]);
    }

    public function store(SignInRequest $request): RedirectResponse
    {
        $signIn = $request->toData();

        // MySQL's default collation matches an address with its accented variants: they share its count.
        $throttleKey = Str::transliterate($signIn->email.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        $credentials = ['email' => $signIn->email, 'password' => $signIn->password];

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::getLastAttempted();

        // Auth::attempt() would rehash too: stored hashes follow the hashing options as users sign in.
        if (Config::boolean('hashing.rehash_on_login')) {
            Auth::getProvider()->rehashPasswordIfRequired($user, $credentials);
        }

        // The password alone does not sign this user in: the challenge does, with what it keeps here.
        if ($user instanceof User && $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put('pending_sign_in', ['user_id' => $user->id, 'remember' => $signIn->remember]);

            return to_route('two-factor-challenge.create');
        }

        Auth::login($user, $signIn->remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
