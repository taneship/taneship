<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

it('renders the dashboard for a signed-in user', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('dashboard')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('turns guests away', function (): void {
    $this->getJson(route('dashboard'))->assertUnauthorized();
});
