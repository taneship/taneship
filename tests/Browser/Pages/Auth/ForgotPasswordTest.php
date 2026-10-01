<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('renders the forgot-password page in light mode', function (): void {
    visit(route('password.request'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.forgot_password.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the forgot-password page in dark mode', function (): void {
    visit(route('password.request'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.forgot_password.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the error of an address the server refuses', function (string $mode): void {
    visit(route('password.request'))
        ->{$mode}()
        // The browser accepts two dots in a row, which the server's email rule refuses.
        ->type('email', 'jane..doe@example.com')
        ->press('[type="submit"]')
        ->assertSee(trans('validation.email', ['attribute' => 'email']))
        // The button fades back in when the request ends: axe would measure its contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('sends a link and says so in a toast', function (string $mode): void {
    Notification::fake();
    $user = User::factory()->create();

    visit(route('password.request'))
        ->{$mode}()
        ->type('email', $user->email)
        ->press('[type="submit"]')
        ->assertSee(trans('identity.password.sent'))
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);

    Notification::assertSentToTimes($user, ResetPassword::class);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('links to the sign-in page', function (): void {
    visit(route('password.request'))
        ->click(trans('identity.forgot_password.sign_in'))
        ->assertPathIs('/login')
        ->assertNoSmoke();
});
