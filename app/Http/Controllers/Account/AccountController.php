<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\DeleteUser;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class AccountController
{
    public function destroy(Request $request, #[CurrentUser] User $user, DeleteUser $deleteUser): RedirectResponse
    {
        // Signing out saves a new remember token: on a deleted user, that would insert them again.
        Auth::logout();

        $deleteUser->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Both asked through the new session, for the next page: the entries the Security page encrypted
        // lose their key, and the toast outlives the session the deletion ended.
        Inertia::clearHistory();
        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.deleted')]);

        return to_route('home');
    }
}
