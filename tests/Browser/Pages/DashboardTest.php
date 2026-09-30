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
