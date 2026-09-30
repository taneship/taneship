<?php

declare(strict_types=1);

it('renders the http error page in light mode', function (): void {
    visit('/missing')
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertSee(trans('foundation.http_error.404.title'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the http error page in dark mode', function (): void {
    visit('/missing')
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertSee(trans('foundation.http_error.404.title'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});
