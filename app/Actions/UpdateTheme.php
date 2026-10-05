<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Theme;
use App\Models\User;

final readonly class UpdateTheme
{
    public function handle(User $user, Theme $theme): void
    {
        $user->theme = $theme;
        $user->save();
    }
}
