<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;

it('renders the sign-in page for guests', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/login')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true))
            ->missing('passkeyOptions'));
});

it('keeps the authentication options in the session when the page asks for them', function (): void {
    Passkey::factory()->create();

    partialReload(route('login'), 'auth/login', 'passkeyOptions')
        ->assertJsonPath('props.passkeyOptions', json_decode(session('passkeys.authentication_options'), true))
        ->assertJsonPath('props.passkeyOptions.rpId', parse_url(config('app.url'), PHP_URL_HOST))
        ->assertJsonPath('props.passkeyOptions.userVerification', 'required')
        // Any passkey of this site: the browser offers the ones it holds.
        ->assertJsonPath('props.passkeyOptions.allowCredentials', []);
});

it('renews the authentication options each time the page asks for them', function (): void {
    $first = partialReload(route('login'), 'auth/login', 'passkeyOptions')->json('props.passkeyOptions.challenge');

    $second = partialReload(route('login'), 'auth/login', 'passkeyOptions')->json('props.passkeyOptions.challenge');

    expect($second)->not->toBe($first)
        ->and(json_decode(session('passkeys.authentication_options'), true)['challenge'])->toBe($second);
});

it('sends signed-in users from the sign-in page to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect(route('dashboard'));
});

it('signs in and leads to the dashboard', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'))
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

it('leads to the intended url after signing in', function (): void {
    $user = User::factory()->create();

    $this->withSession(['url.intended' => url('/somewhere')])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(url('/somewhere'));
});

it('regenerates the session when signing in', function (): void {
    $user = User::factory()->create();

    $this->startSession();
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);
});

it('remembers the user when asked', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

it('keeps a pending sign-in for users with two-factor authentication, and leads to the challenge', function (bool $remember): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => $remember])
        ->assertRedirect(route('two-factor-challenge.create'))
        ->assertSessionHas('pending_sign_in', ['user_id' => $user->id, 'remember' => $remember])
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertGuest();
})->with(['not remembered' => false, 'remembered' => true]);

it('ignores a pending two-factor setup', function (): void {
    $user = pendingTwoFactorUser();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionMissing('pending_sign_in');

    $this->assertAuthenticatedAs($user);
});

it('compares email addresses in lowercase', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->post(route('login.store'), ['email' => 'Jane@Example.COM', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('rehashes a password hashed with outdated options', function (): void {
    $user = User::factory()->create();
    // The hashed cast refuses a hash made with other options: it goes around the model.
    DB::table('users')->where('id', $user->id)->update(['password' => Hash::make('password', ['rounds' => 5])]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(Hash::needsRehash($user->refresh()->password))->toBeFalse();
});

it('refuses a wrong pair', function (string $email, string $password): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->from(route('login'))
        ->post(route('login.store'), ['email' => $email, 'password' => $password])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->assertGuest();
})->with([
    'wrong password' => ['jane@example.com', 'wrong-password'],
    'unknown address' => ['john@example.com', 'password'],
]);

it('validates the form', function (array $input, array $errors): void {
    $this->post(route('login.store'), $input)->assertSessionHasErrors($errors);

    $this->assertGuest();
})->with([
    'empty' => [[], ['email', 'password']],
    'not an address' => [['email' => 'jane', 'password' => 'password'], ['email']],
]);

it('refuses a sixth attempt within a minute and fires lockout', function (): void {
    Event::fake([Lockout::class]);
    $this->freezeTime();
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => trans('auth.throttle', ['seconds' => 60])]);

    $this->assertGuest();
    Event::assertDispatched(Lockout::class);
});

it('counts attempts per address and ip address', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => $otherUser->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => trans('auth.failed')]);

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

it('clears the count after a successful sign-in', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('logout'));

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
    }
});

it('signs out and leads to the welcome page', function (): void {
    $this->actingAs(User::factory()->create());

    $this->startSession();
    $sessionId = session()->getId();
    $token = session()->token();

    $this->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($token);
});

it('signs out signed-in users only', function (): void {
    $this->post(route('logout'))->assertRedirect(route('login'));
});
