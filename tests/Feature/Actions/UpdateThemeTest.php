<?php

declare(strict_types=1);

use App\Actions\UpdateTheme;
use App\Enums\Theme;
use App\Models\User;

it('saves the theme', function (Theme $theme): void {
    $user = User::factory()->create();

    app(UpdateTheme::class)->handle($user, $theme);

    expect($user->refresh()->theme)->toBe($theme);
})->with(Theme::cases());
