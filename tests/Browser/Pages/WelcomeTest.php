<?php

declare(strict_types=1);

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
