<?php

declare(strict_types=1);

use App\Models\User;

it('renders the welcome page in light mode', function (): void {
    visit(route('home'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertSee(config('app.name'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the welcome page in dark mode', function (): void {
    visit(route('home'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertSee(config('app.name'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('links guests to the sign-in page', function (): void {
    visit(route('home'))
        ->click(trans('identity.welcome.sign_in'))
        ->assertPathIs('/login')
        ->assertNoSmoke();
});

it('links signed-in users to the dashboard', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('home'))
        ->click(trans('foundation.welcome.dashboard'))
        ->assertPathIs('/dashboard')
        ->assertNoSmoke();
});
