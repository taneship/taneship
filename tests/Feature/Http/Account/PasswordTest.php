<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function passwordChangeInput(array $overrides = []): array
{
    return [
        'current_password' => 'password',
        'password' => 'correct horse battery',
        'password_confirmation' => 'correct horse battery',
        ...$overrides,
    ];
}

it('changes the password and leads back with a toast', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->put(route('account.password.update'), passwordChangeInput())
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('account.password.updated')]);

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});

it('refuses a wrong current password on its field', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->put(route('account.password.update'), passwordChangeInput(['current_password' => 'not the password']))
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasErrors(['current_password' => trans('validation.current_password')])
        ->assertInertiaFlashMissing('toast');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('requires a password of 12 characters', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->put(route('account.password.update'), passwordChangeInput([
        'password' => str_repeat('a', 11),
        'password_confirmation' => str_repeat('a', 11),
    ]))->assertSessionHasErrors(['password' => trans('validation.min.string', ['attribute' => 'password', 'min' => 12])]);

    $this->put(route('account.password.update'), passwordChangeInput([
        'password' => str_repeat('a', 12),
        'password_confirmation' => str_repeat('a', 12),
    ]))->assertSessionHasNoErrors();
});

it('validates the form', function (array $input, array $errors): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->put(route('account.password.update'), $input)->assertSessionHasErrors($errors);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
})->with([
    'empty' => [[], ['current_password', 'password']],
    'passwords that differ' => [passwordChangeInput(['password_confirmation' => 'battery horse correct']), ['password']],
]);

it('keeps the session that made the change signed in', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('password.confirm.store'), ['password' => 'password']);
    $this->put(route('account.password.update'), passwordChangeInput())->assertSessionHasNoErrors();

    $this->get(route('dashboard'))->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('signs the other sessions out at their next request', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();
    $signedInBrowser = session()->all();

    switchBrowser();
    signInWithConfirmedPassword($user);
    $this->put(route('account.password.update'), passwordChangeInput())->assertSessionHasNoErrors();

    switchBrowser();
    $this->withSession($signedInBrowser)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('issues the remember cookie again, which signs the browser in once its session is gone', function (): void {
    $user = User::factory()->create();
    $recaller = Auth::guard()->getRecallerName();

    // Kept encrypted, as the browser holds it, and sent with one request at a time.
    $formerCookie = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
        ->getCookie($recaller, decrypt: false)
        ?->getValue();

    $this->post(route('password.confirm.store'), ['password' => 'password']);
    $cookie = $this->call('PUT', route('account.password.update'), passwordChangeInput(), [$recaller => $formerCookie])
        ->assertSessionHasNoErrors()
        ->getCookie($recaller, decrypt: false)
        ?->getValue();

    expect($cookie)->toBeString()->not->toBe($formerCookie);

    switchBrowser();
    $this->call('GET', route('dashboard'), cookies: [$recaller => $cookie])->assertOk();
    $this->assertAuthenticatedAs($user);
});

it('stops a remember cookie issued before the change from signing in', function (): void {
    $user = User::factory()->create();
    $recaller = Auth::guard()->getRecallerName();

    $cookie = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
        ->getCookie($recaller, decrypt: false)
        ?->getValue();

    switchBrowser();
    signInWithConfirmedPassword($user);
    $this->put(route('account.password.update'), passwordChangeInput())->assertSessionHasNoErrors();

    switchBrowser();
    $this->call('GET', route('dashboard'), cookies: [$recaller => $cookie])->assertRedirect(route('login'));
    $this->assertGuest();
});

it('issues no remember cookie to a browser that had none', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->put(route('account.password.update'), passwordChangeInput())
        ->assertSessionHasNoErrors()
        ->assertCookieMissing(Auth::guard()->getRecallerName());
});

it('refuses a seventh request within a minute', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    foreach (range(1, 6) as $request) {
        $this->put(route('account.password.update'), passwordChangeInput(['current_password' => 'not the password']))
            ->assertSessionHasErrors('current_password');
    }

    $this->put(route('account.password.update'), passwordChangeInput())->assertTooManyRequests();

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('asks for the password first', function (): void {
    $this->actingAs(User::factory()->create())
        ->put(route('account.password.update'), passwordChangeInput())
        ->assertRedirect(route('password.confirm'));
});

it('sends unverified users to the verification notice', function (): void {
    signInWithConfirmedPassword(User::factory()->unverified()->create());

    $this->put(route('account.password.update'), passwordChangeInput())->assertRedirect(route('verification.notice'));
});

it('sends guests to the sign-in page', function (): void {
    $this->put(route('account.password.update'), passwordChangeInput())->assertRedirect(route('login'));
});
