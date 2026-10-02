<?php

declare(strict_types=1);

use App\Models\User;
use Pest\Browser\Api\AwaitableWebpage;

function signOutThenEnterPassword(AwaitableWebpage $page, User $user): AwaitableWebpage
{
    return $page->click('[data-slot="sidebar-footer"] button')
        ->click(trans('identity.user_menu.sign_out'))
        ->assertPathIs('/')
        ->click(trans('identity.welcome.sign_in'))
        ->assertPathIs('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/two-factor-challenge');
}

it('enables two-factor authentication, signs out, then signs in with a code, then with a recovery code', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('account.security.edit'))
        ->assertPathIs('/confirm-password')
        ->type('password', 'password')
        ->press('[type="submit"]')
        ->assertPathIs('/account/security')
        ->press(trans('identity.security.two_factor_authentication.enable'))
        ->assertVisible('#code');

    $user->refresh();

    $page->type('code', twoFactorCode($user))
        ->press(trans('identity.security.two_factor_authentication.confirm'))
        ->assertSee(trans('identity.two_factor_authentication.enabled'));

    // The setup took the code of this step: the app shows a new one 30 seconds later.
    $this->travel(30)->seconds();

    signOutThenEnterPassword($page, $user)
        ->type('code', twoFactorCode($user))
        ->press(trans('identity.two_factor_challenge.submit'))
        ->assertPathIs('/dashboard')
        ->assertSee($user->name);

    $recoveryCode = (string) $user->refresh()->two_factor_recovery_codes[0];

    signOutThenEnterPassword($page, $user)
        ->press(trans('identity.two_factor_challenge.use_recovery_code'))
        ->type('recovery_code', $recoveryCode)
        ->press(trans('identity.two_factor_challenge.submit'))
        ->assertPathIs('/dashboard')
        ->assertSee($user->name)
        ->assertNoSmoke();

    expect($user->refresh()->two_factor_recovery_codes)->toHaveCount(7)->not->toContain($recoveryCode);
});
