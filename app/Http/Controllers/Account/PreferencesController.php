<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\UpdateTheme;
use App\Http\Requests\Account\UpdateThemeRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PreferencesController
{
    public function edit(): Response
    {
        return Inertia::render('account/preferences');
    }

    public function update(UpdateThemeRequest $request, #[CurrentUser] User $user, UpdateTheme $updateTheme): RedirectResponse
    {
        // No toast: the page takes the new theme at once.
        $updateTheme->handle($user, $request->toData());

        return back();
    }
}
