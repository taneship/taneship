<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('renders the notice in light mode', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    visit(route('verification.notice'))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.verify_email.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the notice in dark mode', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    visit(route('verification.notice'))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.verify_email.description'))
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('sends the link again', function (string $mode): void {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    visit(route('verification.notice'))
        ->{$mode}()
        ->press(trans('identity.verify_email.resend'))
        ->assertSee(trans('identity.verification.sent'))
        // The toast and the button fade in: axe would measure their contrast halfway.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);

    Notification::assertSentToTimes($user, VerifyEmail::class);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('signs out', function (): void {
    $this->actingAs(User::factory()->unverified()->create());

    visit(route('verification.notice'))
        ->click(trans('identity.verify_email.sign_out'))
        ->assertPathIs('/')
        ->assertSee(trans('identity.welcome.sign_in'))
        ->assertNoSmoke();
});
