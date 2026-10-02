<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;

// A browser cannot be handed a confirmed password: it confirms it, then reloads the page to get its server render.
function confirmPassword(PendingAwaitablePage $page): AwaitableWebpage
{
    return $page
        ->assertPathIs('/confirm-password')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/account/security')
        ->navigate(route('account.security.edit'));
}

it('renders the security page with passkeys', function (string $mode, bool $isDark): void {
    $this->freezeTime();
    $user = User::factory()->create();
    Passkey::factory()->for($user)->withAaguid('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')->create([
        'name' => 'iPhone',
        'created_at' => now()->subMonths(2),
        'last_used_at' => now()->subDays(3),
    ]);
    Passkey::factory()->for($user)->withAaguid('00000000-0000-0000-0000-000000000000')->create([
        'name' => 'Work laptop',
        'created_at' => now()->subHour(),
        'last_used_at' => null,
    ]);

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertTitleContains(trans('identity.security.title'))
        ->assertSee(trans('identity.account_layout.title'))
        ->assertSeeIn('[aria-current="page"]', trans('identity.account_layout.security'))
        ->assertSeeIn('section li:first-child', 'Work laptop')
        ->assertSeeIn('section li:first-child', 'Added 1 hour ago · Never used')
        ->assertSeeIn('section li:last-child', 'iPhone')
        ->assertSeeIn('section li:last-child', 'Apple Passwords · Added 2 months ago · Last used 3 days ago')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('renders the security page without passkeys', function (string $mode, bool $isDark): void {
    $this->actingAs(User::factory()->create());

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertSee(trans('identity.security.passkeys.empty'))
        ->assertVisible('#name')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('shows why the browser could not create the passkey', function (string $mode): void {
    $this->actingAs(User::factory()->create());

    // Browser tests are served on 127.0.0.1, an address WebAuthn refuses as relying party.
    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->type('name', 'MacBook Pro')
        ->press(trans('identity.security.passkeys.add'))
        ->assertSee(trans('identity.use_passkey.not_created'))
        ->assertValue('#name', 'MacBook Pro')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('renders the security page on mobile', function (): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create(['name' => 'iPhone']);

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit'))->on()->mobile()->inLightMode())
        ->assertSee('iPhone')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('opens from the user menu, and closes the menu', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->assertPathIs('/account/security')
        ->assertSee(trans('identity.security.passkeys.title'))
        // The application layout stays mounted from one page to the next: the menu must close.
        ->assertMissing('[data-slot="dropdown-menu-content"]')
        ->assertNoSmoke();
});

it('opens from the user menu on mobile, and closes the sidebar', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->on()->mobile()
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->click('[data-slot="sidebar-trigger"]')
        ->click('[data-mobile="true"] [data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->assertPathIs('/account/security')
        ->assertSee(trans('identity.security.passkeys.title'))
        ->assertMissing('[data-mobile="true"]')
        ->assertNoSmoke();
});
