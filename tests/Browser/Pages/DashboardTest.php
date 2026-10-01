<?php

declare(strict_types=1);

use App\Models\User;

it('renders the dashboard in light mode', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertSee(trans('foundation.dashboard.title'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the dashboard in dark mode', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertSee(trans('foundation.dashboard.title'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the user menu', function (string $mode): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    visit(route('dashboard'))
        ->{$mode}()
        ->assertSeeIn('[data-slot="sidebar-footer"]', $user->name)
        ->assertSeeIn('[data-slot="sidebar-footer"]', $user->email)
        ->click('[data-slot="sidebar-footer"] button')
        ->assertVisible('[data-slot="dropdown-menu-content"]')
        ->assertSeeIn('[data-slot="dropdown-menu-content"]', trans('identity.user_menu.sign_out'))
        // The menu fades in: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('signs out from the user menu', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/')
        ->assertSee(trans('identity.welcome.sign_in'))
        ->assertNoSmoke();
});

it('signs out from the user menu on mobile', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-slot="sidebar-trigger"]')
        ->click('[data-mobile="true"] [data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/')
        ->assertSee(trans('identity.welcome.sign_in'))
        ->assertNoSmoke();
});
