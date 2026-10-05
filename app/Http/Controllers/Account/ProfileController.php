<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\UpdateProfile;
use App\Data\ProfileData;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
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
        $updateProfile->handle($user, $request->toData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('account.profile.updated')]);

        return back();
    }
}
