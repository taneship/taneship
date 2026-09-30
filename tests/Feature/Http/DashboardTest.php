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
            ->where('isSidebarOpen', true)
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('shares the sidebar state the browser remembers', function (string $cookie, bool $isSidebarOpen): void {
    $this->actingAs(User::factory()->create())
        ->withUnencryptedCookie('sidebar_state', $cookie)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('isSidebarOpen', $isSidebarOpen));
})->with([
    'collapsed' => ['false', false],
    'expanded' => ['true', true],
]);

it('turns guests away', function (): void {
    $this->getJson(route('dashboard'))->assertUnauthorized();
});
