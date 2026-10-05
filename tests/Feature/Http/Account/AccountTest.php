<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

it('deletes the account and leads to the welcome page with a toast', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);

    $this->delete(route('account.destroy'))
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('account.deleted')]);

    $this->assertGuest();
    $this->assertModelMissing($user);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('welcome')
            ->where('user', null)
            ->hasFlash('toast', ['type' => 'success', 'message' => trans('account.deleted')])
            ->etc());
});

it('ends the session', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->startSession();
    $sessionId = session()->getId();
    $token = session()->token();

    $this->delete(route('account.destroy'));

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->toBeString()->not->toBe($token)
        ->and(session()->has('auth.password_confirmed_at'))->toBeFalse();
});

it('has the browser clear its history keys on the page that follows the deletion, and only there', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->delete(route('account.destroy'));

    expect($this->get(route('home'))->inertiaPage())->toHaveKey('clearHistory', true)
        ->and($this->get(route('home'))->inertiaPage())->not->toHaveKey('clearHistory');
});

it('leaves the other sessions as guests at their next request', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();
    $signedInBrowser = session()->all();

    switchBrowser();
    signInWithConfirmedPassword($user);
    $this->delete(route('account.destroy'))->assertRedirect(route('home'));

    switchBrowser();
    $this->withSession($signedInBrowser)->get(route('home'))->assertOk();
    $this->assertGuest();
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('stops a remember cookie of the account from signing in', function (): void {
    $user = User::factory()->create();
    $recaller = Auth::guard()->getRecallerName();

    // Kept encrypted, as the browser holds it, and sent with one request at a time.
    $cookie = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password', 'remember' => 'on'])
        ->getCookie($recaller, decrypt: false)
        ?->getValue();

    $this->post(route('password.confirm.store'), ['password' => 'password']);
    $this->call('DELETE', route('account.destroy'), cookies: [$recaller => $cookie])->assertRedirect(route('home'));

    expect(User::query()->count())->toBe(0);

    switchBrowser();
    $this->call('GET', route('dashboard'), cookies: [$recaller => $cookie])->assertRedirect(route('login'));
    $this->assertGuest();
});

it('reaches the deletion once the routes are cached', function (): void {
    // Cached routes match in the order they were registered: a route of the same path
    // that answers every method, registered first, would take the request.
    ['compiled' => $compiled, 'attributes' => $attributes] = Route::getRoutes()->compile();
    $cachedRoutes = new CompiledRouteCollection($compiled, $attributes)->setRouter(app('router'))->setContainer(app());

    expect($cachedRoutes->match(Request::create('/account', 'DELETE'))->getName())->toBe('account.destroy');
});

it('asks for the password first', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('account.destroy'))
        ->assertRedirect(route('password.confirm'));

    $this->assertModelExists($user);
});

it('sends unverified users to the verification notice', function (): void {
    $user = User::factory()->unverified()->create();
    signInWithConfirmedPassword($user);

    $this->delete(route('account.destroy'))->assertRedirect(route('verification.notice'));

    $this->assertModelExists($user);
});

it('sends guests to the sign-in page', function (): void {
    $this->delete(route('account.destroy'))->assertRedirect(route('login'));
});
