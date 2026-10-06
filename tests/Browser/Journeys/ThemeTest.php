<?php

declare(strict_types=1);

use App\Models\User;

it('chooses a theme, signs out to the system theme and finds the theme again on sign-in', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    visit(route('login'))
        ->inLightMode()
        ->type('email', 'jane@example.com')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->navigate(route('account.preferences.edit'))
        ->click(trans('account.preferences.theme.dark'))
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->click(trans('identity.welcome.sign_in'))
        ->type('email', 'jane@example.com')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        // The class is in the server's response, before any script runs.
        ->assertScript('async () => (await (await fetch("/dashboard")).text()).includes(\'<html lang="en" dir="ltr" class="dark">\')', true)
        ->assertNoSmoke();
});
