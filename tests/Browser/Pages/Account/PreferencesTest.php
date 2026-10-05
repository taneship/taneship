<?php

declare(strict_types=1);

use App\Enums\Theme;
use App\Models\User;

it('renders the preferences page', function (string $mode, Theme $theme): void {
    $this->actingAs(User::factory()->create(['theme' => $theme]));

    visit(route('account.preferences.edit'))
        ->{$mode}()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertTitleContains(trans('account.preferences.title'))
        ->assertSeeIn('[aria-current="page"]', trans('account.account_layout.preferences'))
        // The checked radio, by the label assistive technology reads.
        ->assertScript('document.getElementById(document.querySelector(\'[role="radio"][aria-checked="true"]\').getAttribute("aria-labelledby")).textContent', trans('account.preferences.theme.'.$theme->value))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with([
    'light mode' => ['inLightMode', Theme::Light],
    'dark mode' => ['inDarkMode', Theme::Dark],
]);

it('applies the chosen theme at once', function (): void {
    $user = User::factory()->create(['theme' => Theme::Light]);

    $this->actingAs($user);

    visit(route('account.preferences.edit'))
        ->inLightMode()
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->click(trans('account.preferences.theme.dark'))
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->click(trans('account.preferences.theme.system'))
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertNoSmoke();

    expect($user->refresh()->theme)->toBe(Theme::System);
});

it('opens from the account settings menu', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('account.profile.edit'))
        ->click(trans('account.account_layout.preferences'))
        ->assertPathIs('/account/preferences')
        ->assertNoSmoke();
});
