<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

it('renders the security page for a user who confirmed their password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('account/security')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', ['name' => $user->name, 'email' => $user->email])
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true))
            ->where('passkeys', []));
});

it('lists the passkeys of the user, newest first', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();

    $oldest = Passkey::factory()->for($user)->create([
        'name' => 'iPhone',
        'credential' => ['aaguid' => 'fbfc3007-154e-4ecc-8c0b-6e020557d7bd'],
        'created_at' => now()->subMonths(2),
        'last_used_at' => now()->subDays(3),
    ]);
    $newest = Passkey::factory()->for($user)->create([
        'name' => 'Work laptop',
        'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
        'created_at' => now()->subHour(),
        'last_used_at' => null,
    ]);
    Passkey::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('passkeys', [
            ['id' => $newest->id, 'name' => 'Work laptop', 'holder' => null, 'added' => '1 hour ago', 'lastUsed' => null],
            ['id' => $oldest->id, 'name' => 'iPhone', 'holder' => 'Apple Passwords', 'added' => '2 months ago', 'lastUsed' => '3 days ago'],
        ]));
});

it('names holders from the configuration, so a newer list renames existing passkeys', function (): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create(['credential' => ['aaguid' => 'fbfc3007-154e-4ecc-8c0b-6e020557d7bd']]);

    config(['passkeys.authenticators' => ['fbfc3007-154e-4ecc-8c0b-6e020557d7bd' => 'iCloud Keychain']]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('passkeys.0.holder', 'iCloud Keychain'));
});

it('asks for the password first', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('account.security.edit'))
        ->assertRedirect(route('password.confirm'));
});

it('sends unverified users to the verification notice', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertRedirect(route('verification.notice'));
});

it('sends guests to the sign-in page', function (): void {
    $this->get(route('account.security.edit'))->assertRedirect(route('login'));
});
