<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\UpdateProfile;
use App\Data\ProfileData;
use App\Exceptions\EmailAlreadyTakenException;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('account/profile', [
            'profile' => new ProfileData($user->name, $user->email),
            'hasVerifiedEmail' => $user->hasVerifiedEmail(),
        ]);
    }

    public function update(UpdateProfileRequest $request, #[CurrentUser] User $user, UpdateProfile $updateProfile): RedirectResponse
    {
        try {
            $updateProfile->handle($user, $request->toData());
        } catch (EmailAlreadyTakenException) {
            // The form request found the address free, and another request took it since: the answer is the rule's.
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.updated')]);

        return back();
    }
}
