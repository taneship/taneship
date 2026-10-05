<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\ChangePassword;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class PasswordController
{
    public function update(ChangePasswordRequest $request, #[CurrentUser] User $user, ChangePassword $changePassword): RedirectResponse
    {
        $changePassword->handle($user, $request->toData());

        // The remember cookie carries the former password hash: kept, it would sign this browser out
        // once its session expires. Signing in again is the guard's way to issue it with the new one.
        if ($request->hasCookie(Auth::getRecallerName())) {
            Auth::login($user, remember: true);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.password.updated')]);

        return back();
    }
}
