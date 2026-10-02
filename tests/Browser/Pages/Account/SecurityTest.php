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
        ->assertSeeIn('[aria-labelledby="passkeys"] li:first-child', 'Work laptop')
        ->assertSeeIn('[aria-labelledby="passkeys"] li:first-child', 'Added 1 hour ago · Never used')
        ->assertSeeIn('[aria-labelledby="passkeys"] li:last-child', 'iPhone')
        ->assertSeeIn('[aria-labelledby="passkeys"] li:last-child', 'Apple Passwords · Added 2 months ago · Last used 3 days ago')
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

it('renders two-factor authentication disabled', function (string $mode, bool $isDark): void {
    $this->actingAs(User::factory()->create());

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertSee(trans('identity.security.two_factor_authentication.title'))
        ->assertSee(trans('identity.security.two_factor_authentication.enable'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('renders a pending two-factor setup', function (string $mode, bool $isDark): void {
    $user = pendingTwoFactorUser();

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertVisible('img[alt="'.trans('identity.security.two_factor_authentication.qr_code').'"]')
        ->assertScript('document.querySelector(\'[aria-labelledby="two-factor-authentication"] img\').naturalWidth', 192)
        ->assertSee(trans('identity.security.two_factor_authentication.scan'))
        ->assertSee((string) $user->two_factor_secret)
        ->assertVisible('#code')
        ->assertDontSee(trans('identity.security.two_factor_authentication.enable'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('renders two-factor authentication enabled', function (string $mode, bool $isDark): void {
    $this->actingAs(User::factory()->withTwoFactorAuthentication()->create());

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertSee(trans('identity.security.two_factor_authentication.enabled'))
        ->assertDontSee(trans('identity.security.two_factor_authentication.enable'))
        ->assertMissing('#code')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('enables two-factor authentication with a first code, then shows the recovery codes', function (string $mode): void {
    $this->freezeTime();
    $user = User::factory()->create();

    $this->actingAs($user);

    $page = confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->press(trans('identity.security.two_factor_authentication.enable'))
        ->assertVisible('#code');

    $user->refresh();

    $page->type('code', twoFactorCode($user))
        ->press(trans('identity.security.two_factor_authentication.confirm'))
        ->assertSee(trans('identity.two_factor_authentication.enabled'))
        ->assertSee(trans('identity.security.two_factor_authentication.recovery_codes.title'));

    foreach ((array) $user->two_factor_recovery_codes as $recoveryCode) {
        $page->assertSeeIn('[aria-labelledby="two-factor-authentication"] ul', (string) $recoveryCode);
    }

    $page->assertNoSmoke()->assertNoAccessibilityIssues(level: 3);

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('shows why a code is refused, and clears it', function (string $mode): void {
    $this->freezeTime();
    $user = pendingTwoFactorUser();

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->type('code', twoFactorCode($user, 2))
        ->press(trans('identity.security.two_factor_authentication.confirm'))
        ->assertSee(trans('identity.two_factor_authentication.invalid_code'))
        ->assertValue('#code', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);

    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

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

it('starts no ceremony for a name made of spaces', function (): void {
    $this->actingAs(User::factory()->create());

    // The browser stops the form at its empty required field, before the page asks for the options.
    confirmPassword(visit(route('account.security.edit')))
        ->type('name', '   ')
        ->assertValue('#name', '')
        ->press(trans('identity.security.passkeys.add'))
        ->assertScript('document.querySelector("#name").matches(":invalid")')
        ->assertScript('history.state.page.props.passkeyOptions === undefined')
        ->assertDontSee(trans('identity.use_passkey.not_created'))
        ->assertNoSmoke();
});

it('asks before removing a passkey', function (string $mode, bool $isDark): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create(['name' => 'iPhone']);

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit'))->{$mode}())
        ->click('[aria-label="Remove iPhone"]')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertSeeIn('[role="alertdialog"]', 'Remove iPhone?')
        ->assertSeeIn('[role="alertdialog"]', trans('identity.security.passkeys.removal.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('removes a passkey once confirmed', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user)->create(['name' => 'iPhone']);

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit')))
        ->click('[aria-label="Remove iPhone"]')
        ->press(trans('identity.security.passkeys.removal.confirm'))
        ->assertSee(trans('identity.passkeys.removed'))
        ->assertSee(trans('identity.security.passkeys.empty'))
        ->assertMissing('[role="alertdialog"]')
        ->assertNoSmoke();

    $this->assertModelMissing($passkey);
});

it('keeps the passkey when the removal is canceled', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user)->create(['name' => 'iPhone']);

    $this->actingAs($user);

    confirmPassword(visit(route('account.security.edit')))
        ->click('[aria-label="Remove iPhone"]')
        ->press(trans('identity.security.passkeys.removal.cancel'))
        ->assertMissing('[role="alertdialog"]')
        ->assertSeeIn('[aria-labelledby="passkeys"] > ul', 'iPhone')
        ->assertNoSmoke();

    $this->assertModelExists($passkey);
});

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
