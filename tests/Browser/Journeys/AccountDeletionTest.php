<?php

declare(strict_types=1);

use App\Models\User;

it('signs in, deletes the account and fails to sign in again', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    visit(route('login'))
        ->type('email', 'jane@example.com')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/dashboard')
        ->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.account_settings'))
        ->assertPathIs('/account/profile')
        ->click(trans('identity.account_layout.security'))
        ->assertPathIs('/confirm-password')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/account/security')
        ->click('[aria-labelledby="delete-account"] button')
        ->click('[data-slot="alert-dialog-action"]')
        ->assertPathIs('/')
        ->assertSee(trans('account.deleted'))
        ->click(trans('identity.welcome.sign_in'))
        ->type('email', 'jane@example.com')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/login')
        ->assertSee(trans('auth.failed'))
        ->assertNoSmoke();

    expect(User::query()->count())->toBe(0);
});
