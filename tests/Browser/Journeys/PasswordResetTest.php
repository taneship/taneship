<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('resets a forgotten password', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $page = visit(route('login'))
        ->click(trans('identity.login.forgot_password'))
        ->assertPathIs('/forgot-password')
        ->type('email', 'jane@example.com')
        ->press('[type="submit"]')
        ->assertSee(trans('identity.password.sent'));

    // Built once the page is served: the link points at the address the browser opens.
    $link = Notification::sent($user, ResetPassword::class)->sole()->toMail($user)->actionUrl;

    $page->navigate($link)
        ->assertValue('email', 'jane@example.com')
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee(trans('identity.password.reset'))
        ->type('email', 'jane@example.com')
        ->type('password', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->assertNoSmoke();
});
