<?php

declare(strict_types=1);

use App\Actions\DisableTwoFactorAuthentication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-02 09:30:10'));
});

// The first half of sign-in: the password is right, the challenge remains.
function enterPassword(User $user, bool $remember = false): void
{
    test()->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => $remember])
        ->assertRedirect(route('two-factor-challenge.create'));
}

it('renders the challenge page once the password is right', function (): void {
    enterPassword(User::factory()->withTwoFactorAuthentication()->create());

    $this->get(route('two-factor-challenge.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/two-factor-challenge')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('leads to sign-in without a pending sign-in', function (): void {
    $this->get(route('two-factor-challenge.create'))->assertRedirect(route('login'));

    $this->post(route('two-factor-challenge.store'), ['code' => '123456'])->assertRedirect(route('login'));

    $this->assertGuest();
});

it('leads to sign-in once two-factor authentication is turned off', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    app(DisableTwoFactorAuthentication::class)->handle($user);

    $this->get(route('two-factor-challenge.create'))->assertRedirect(route('login'));
});

it('leads to sign-in once the user is deleted', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    $user->delete();

    $this->get(route('two-factor-challenge.create'))->assertRedirect(route('login'));
});

it('sends signed-in users to the dashboard', function (): void {
    $this->actingAs(User::factory()->create());

    $this->get(route('two-factor-challenge.create'))->assertRedirect(route('dashboard'));

    $this->post(route('two-factor-challenge.store'))->assertRedirect(route('dashboard'));
});

it('signs in with a code and leads to the dashboard', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('pending_sign_in')
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

it('signs in with a recovery code, which then stops working', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCode = (string) $user->two_factor_recovery_codes[0];
    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('pending_sign_in');

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->two_factor_recovery_codes)->toHaveCount(7)->not->toContain($recoveryCode);
});

it('remembers the user when asked at sign-in', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user, remember: true);

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)])
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

it('leads to the intended url after signing in', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $this->withSession(['url.intended' => url('/somewhere')]);
    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)])
        ->assertRedirect(url('/somewhere'));
});

it('regenerates the session when signing in', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)]);

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);
});

it('refuses a wrong code on the code field, and keeps the pending sign-in', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    $this->from(route('two-factor-challenge.create'))
        ->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)])
        ->assertRedirect(route('two-factor-challenge.create'))
        ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')])
        ->assertSessionHas('pending_sign_in.user_id', $user->id);

    $this->assertGuest();
});

it('refuses a code already accepted', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $code = twoFactorCode($user);
    enterPassword($user);
    $this->post(route('two-factor-challenge.store'), ['code' => $code]);
    $this->post(route('logout'));
    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['code' => $code])
        ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')]);

    $this->assertGuest();
});

it('refuses a wrong recovery code on the recovery code field', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $recoveryCodes = $user->two_factor_recovery_codes;
    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['recovery_code' => 'abcdefghij-klmnopqrst'])
        ->assertSessionHasErrors(['recovery_code' => trans('identity.two_factor_authentication.recovery_codes.invalid')]);

    $this->assertGuest();
    expect($user->refresh()->two_factor_recovery_codes)->toBe($recoveryCodes);
});

it('validates the form', function (array $input, array $errors): void {
    enterPassword(User::factory()->withTwoFactorAuthentication()->create());

    $this->post(route('two-factor-challenge.store'), $input)->assertSessionHasErrors($errors);

    $this->assertGuest();
})->with([
    'empty' => [[], ['code', 'recovery_code']],
    'five digits' => [['code' => '12345'], ['code']],
    'not digits' => [['code' => 'abcdef'], ['code']],
]);

it('refuses a recovery code beyond 255 characters', function (): void {
    enterPassword(User::factory()->withTwoFactorAuthentication()->create());

    $this->post(route('two-factor-challenge.store'), ['recovery_code' => str_repeat('a', 256)])
        ->assertSessionHasErrors(['recovery_code' => trans('validation.max.string', ['attribute' => 'recovery code', 'max' => 255])]);

    $this->assertGuest();
});

it('refuses a sixth attempt within a minute on the field in use, and fires lockout', function (string $field, Closure $answer): void {
    Event::fake([Lockout::class]);
    $this->freezeTime();
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)])
            ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')]);
    }

    $this->post(route('two-factor-challenge.store'), [$field => $answer($user)])
        ->assertSessionHasErrors([$field => trans('auth.throttle', ['seconds' => 60])]);

    $this->assertGuest();
    Event::assertDispatched(Lockout::class);
})->with([
    'code' => ['code', fn (User $user): string => twoFactorCode($user)],
    'recovery code' => ['recovery_code', fn (User $user): string => (string) $user->two_factor_recovery_codes[0]],
]);

it('keeps counting when the password is entered again', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)]);
    }

    enterPassword($user);

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)])
        ->assertSessionHasErrors(['code' => trans('auth.throttle', ['seconds' => 60])]);
});

it('counts the attempts of each user apart', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    $otherUser = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)]);
    }

    enterPassword($otherUser);

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($otherUser)])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($otherUser);
});

it('clears the count after the challenge is passed', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    enterPassword($user);

    foreach (range(1, 4) as $attempt) {
        $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)]);
    }

    $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user)]);
    $this->post(route('logout'));
    enterPassword($user);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('two-factor-challenge.store'), ['code' => twoFactorCode($user, 2)])
            ->assertSessionHasErrors(['code' => trans('identity.two_factor_authentication.invalid_code')]);
    }
});
