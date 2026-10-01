<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('signs up, verifies the email address, signs out and signs in', function (): void {
    Notification::fake();

    $page = visit(route('register'))
        ->type('name', 'Jane Doe')
        ->type('email', 'jane@example.com')
        ->type('password', 'correct horse battery')
        ->type('password_confirmation', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/email/verify')
        ->assertSee(trans('identity.verify_email.title'));

    $user = User::query()->sole();
    // Built once the page is served: the link is signed for the address the browser opens.
    $link = Notification::sent($user, VerifyEmail::class)->sole()->toMail($user)->actionUrl;

    $page->navigate($link)
        ->assertPathIs('/dashboard')
        ->assertSee(trans('identity.verification.verified'))
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/')
        ->click(trans('identity.welcome.sign_in'))
        ->type('email', 'jane@example.com')
        ->type('password', 'correct horse battery')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->assertSee('Jane Doe')
        ->assertNoSmoke();
});
