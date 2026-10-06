<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

it('renders the welcome page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('welcome')
            ->where('name', config('app.name'))
            ->where('errors', [])
            ->where('direction', 'ltr')
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('shares the signed-in user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('user', ['name' => $user->name, 'email' => $user->email]));
});

it('shares the translations once per locale', function (): void {
    $version = $this->get(route('home'))->inertiaPage()['version'];

    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version ?? ''])
        ->get(route('home'))
        ->assertOk()
        ->assertJsonPath('onceProps', ['translations.en' => ['prop' => 'translations', 'expiresAt' => null]]);
});

it('does not send the translations again to a visit that has them', function (): void {
    $version = $this->get(route('home'))->inertiaPage()['version'];

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version ?? '',
        'X-Inertia-Except-Once-Props' => 'translations.en',
    ])
        ->get(route('home'))
        ->assertOk()
        ->assertJsonMissingPath('props.translations');
});
