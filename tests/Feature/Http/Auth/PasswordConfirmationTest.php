<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

it('renders the confirmation page for signed-in users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/confirm-password')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', ['name' => $user->name, 'email' => $user->email])
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('sends guests from the confirmation page to the sign-in page', function (): void {
    $this->get(route('password.confirm'))->assertRedirect(route('login'));
});

it('confirms the password and leads to the dashboard', function (): void {
    $this->freezeTime();

    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('auth.password_confirmed_at', now()->unix());
});

it('leads to the intended url after confirming the password', function (): void {
    $this->actingAs(User::factory()->create())
        ->withSession(['url.intended' => url('/somewhere')])
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(url('/somewhere'));
});

it('refuses a wrong password', function (): void {
    $this->actingAs(User::factory()->create())
        ->from(route('password.confirm'))
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertRedirect(route('password.confirm'))
        ->assertSessionHasErrors(['password' => trans('validation.current_password')])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('validates the form', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'))
        ->assertSessionHasErrors(['password'])
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('confirms passwords for signed-in users only', function (): void {
    $this->post(route('password.confirm.store'), ['password' => 'password'])->assertRedirect(route('login'));
});

it('refuses a seventh attempt within a minute', function (): void {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.confirm.store'), ['password' => 'wrong-password'])->assertRedirect();
    }

    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertTooManyRequests()
        ->assertSessionMissing('auth.password_confirmed_at');
});

it('leads from a guarded page to the confirmation page, then back to it', function (): void {
    Route::middleware(['web', 'auth', 'password.confirm'])->get('/guarded', fn (): string => 'Guarded.');

    $this->actingAs(User::factory()->create())
        ->get('/guarded')
        ->assertRedirect(route('password.confirm'));

    $this->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(url('/guarded'));

    $this->get('/guarded')->assertOk();
});

it('keeps the confirmation for three hours', function (): void {
    $this->freezeTime();
    Route::middleware(['web', 'auth', 'password.confirm'])->get('/guarded', fn (): string => 'Guarded.');

    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'password']);

    $this->travel(3)->hours();

    $this->get('/guarded')->assertOk();

    $this->travel(1)->seconds();

    $this->get('/guarded')->assertRedirect(route('password.confirm'));
});
