<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('renders the profile page', function (string $mode, bool $isDark): void {
    $this->actingAs(User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']));

    visit(route('account.profile.edit'))
        ->{$mode}()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", $isDark)
        ->assertTitleContains(trans('account.profile.title'))
        ->assertSee(trans('identity.account_layout.title'))
        ->assertSeeIn('[aria-current="page"]', trans('account.account_layout.profile'))
        ->assertValue('name', 'Jane Doe')
        ->assertValue('email', 'jane@example.com')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => ['inLightMode', false], 'dark mode' => ['inDarkMode', true]]);

it('renders the profile page of an unverified address', function (string $mode): void {
    $this->actingAs(User::factory()->unverified()->create());

    visit(route('account.profile.edit'))
        ->{$mode}()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertSee(trans('account.profile.unverified'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('sends the link again', function (string $mode): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    visit(route('account.profile.edit'))
        ->{$mode}()
        ->press(trans('account.profile.resend'))
        ->assertSee(trans('identity.verification.sent'))
        // The toast fades in: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);

    Notification::assertSentToTimes($user, VerifyEmail::class);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('asks a new address to be verified', function (): void {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user);

    visit(route('account.profile.edit'))
        ->assertDontSee(trans('account.profile.unverified'))
        ->clear('email')
        ->type('email', 'jane.smith@example.com')
        ->press(trans('account.profile.save'))
        ->assertSee(trans('account.profile.unverified'))
        ->assertNoSmoke();

    Notification::assertSentToTimes($user, VerifyEmail::class);
});

it('shows the error of an address already taken', function (string $mode): void {
    User::factory()->create(['email' => 'john@example.com']);
    $this->actingAs(User::factory()->create());

    visit(route('account.profile.edit'))
        ->{$mode}()
        ->clear('email')
        ->type('email', 'john@example.com')
        ->press(trans('account.profile.save'))
        ->assertSee(trans('validation.unique', ['attribute' => 'email']))
        // The text of an invalid field fades to red: axe would measure the color it starts from.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('renames the user', function (): void {
    $this->actingAs(User::factory()->create(['name' => 'Jane Doe']));

    visit(route('account.profile.edit'))
        ->clear('name')
        ->type('name', 'Jane Smith')
        ->press(trans('account.profile.save'))
        ->assertSee(trans('account.profile.updated'))
        ->assertSeeIn('[data-slot="sidebar-footer"]', 'Jane Smith')
        ->assertNoSmoke();
});

it('shows the name and the address as they are saved', function (): void {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    visit(route('account.profile.edit'))
        ->clear('name')
        ->type('name', 'Jane Smith  ')
        ->clear('email')
        ->type('email', 'Jane.Smith@Example.COM')
        ->press(trans('account.profile.save'))
        ->assertSee(trans('account.profile.updated'))
        ->assertValue('name', 'Jane Smith')
        ->assertValue('email', 'jane.smith@example.com')
        ->assertNoSmoke();
});

it('renames the user on mobile', function (): void {
    $this->actingAs(User::factory()->create(['name' => 'Jane Doe']));

    visit(route('account.profile.edit'))
        ->on()->mobile()
        ->clear('name')
        ->type('name', 'Jane Smith')
        ->press(trans('account.profile.save'))
        ->assertSee(trans('account.profile.updated'))
        ->assertValue('name', 'Jane Smith')
        ->assertNoSmoke();
});

it('opens from the user menu, and closes the menu', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->assertPathIs('/account/profile')
        ->assertSee(trans('account.profile.description'))
        // The application layout stays mounted from one page to the next: the menu must close.
        ->assertMissing('[data-slot="dropdown-menu-content"]')
        ->assertNoSmoke();
});

it('opens from the user menu on mobile, and closes the sidebar', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->on()->mobile()
        ->click('[data-slot="sidebar-trigger"]')
        ->click('[data-mobile="true"] [data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->assertPathIs('/account/profile')
        ->assertSee(trans('account.profile.description'))
        ->assertMissing('[data-mobile="true"]')
        ->assertNoSmoke();
});

it('leads from /account to the profile page', function (): void {
    $this->actingAs(User::factory()->create());

    visit('/account')
        ->assertPathIs('/account/profile')
        ->assertNoSmoke();
});
