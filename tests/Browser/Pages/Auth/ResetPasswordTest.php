<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('renders the reset page in light mode', function (): void {
    $user = User::factory()->create();

    visit(route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email]))
        ->inLightMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", false)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.reset_password.description'))
        ->assertValue('email', $user->email)
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('renders the reset page in dark mode', function (): void {
    $user = User::factory()->create();

    visit(route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email]))
        ->inDarkMode()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertScript("document.documentElement.classList.contains('dark')", true)
        ->assertAttribute('meta[name="robots"]', 'content', 'noindex')
        ->assertSee(trans('identity.reset_password.description'))
        ->assertValue('email', $user->email)
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
});

it('shows the error of an invalid token', function (string $mode): void {
    $user = User::factory()->create();

    visit(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->{$mode}()
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertSee(trans('passwords.token'))
        // The text of an invalid field fades to red: axe would measure the color it starts from.
        ->assertScript('async () => { await Promise.all(document.getAnimations().map((animation) => animation.finished)); return true; }')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertValue('password', '')
        ->assertNoSmoke()
        ->assertNoAccessibilityIssues(level: 3);
})->with(['light mode' => 'inLightMode', 'dark mode' => 'inDarkMode']);

it('resets the password and leads to sign-in', function (): void {
    $user = User::factory()->create();

    visit(route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email]))
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee(trans('identity.password.reset'))
        ->assertNoSmoke();

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});
