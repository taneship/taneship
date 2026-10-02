<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

it('renders the confirmation page in light mode', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.confirm_password.description'))
        ->assertDontSee(trans('identity.confirm_password.passkey'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the confirmation page in dark mode', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.confirm_password.description'))
        ->assertDontSee(trans('identity.confirm_password.passkey'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the error of a wrong password', function (string $mode): void {
    $this->actingAs(User::factory()->create());

    visit(route('password.confirm'))
        ->{$mode}()
        ->type('password', 'wrong-password')
        ->press('[type="submit"]')
        ->assertSee(trans('validation.current_password'))
        // The button fades back in when the request ends: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#password', 'aria-invalid', 'true')
        ->assertValue('password', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('offers a passkey to users who have one', function (string $mode): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create();
    $this->actingAs($user);

    visit(route('password.confirm'))
        ->{$mode}()
        ->assertSee(trans('identity.confirm_password.passkey'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('shows why the browser could not use a passkey', function (string $mode): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create();
    $this->actingAs($user);

    // Browser tests are served on 127.0.0.1, an address WebAuthn refuses as relying party.
    visit(route('password.confirm'))
        ->{$mode}()
        ->press(trans('identity.confirm_password.passkey'))
        ->assertSee(trans('identity.use_passkey.not_used'))
        ->assertAttribute('button[aria-describedby="credential-error"]', 'type', 'button')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('leads back to the page that asked for the password', function (): void {
    Route::middleware(['web', 'auth', 'password.confirm'])->get('/guarded', fn (): Response => Inertia::render('dashboard'));
    $this->actingAs(User::factory()->create());

    visit('/guarded')
        ->assertPathIs('/confirm-password')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/guarded')
        ->assertSee(trans('foundation.dashboard.title'))
        ->assertNoSmoke();
});
