<?php

declare(strict_types=1);

use App\Enums\Theme;
use App\Models\User;
use Illuminate\Support\Facades\Vite;
use Inertia\Testing\AssertableInertia;

const DARK_SCHEME_SCRIPT = "window.matchMedia('(prefers-color-scheme: dark)')";

it('shares the system theme with guests', function (): void {
    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('theme', 'system'));
});

it('shares the theme of the signed-in user', function (): void {
    $this->actingAs(User::factory()->create(['theme' => Theme::Dark]))
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('theme', 'dark'));
});

it('marks the first response dark when the theme is dark', function (): void {
    $this->actingAs(User::factory()->create(['theme' => Theme::Dark]))
        ->get(route('dashboard'))
        ->assertSee('<html lang="en" class="dark">', false)
        ->assertDontSee(DARK_SCHEME_SCRIPT, false);
});

it('leaves the first response light when the theme is light', function (): void {
    $this->actingAs(User::factory()->create(['theme' => Theme::Light]))
        ->get(route('dashboard'))
        ->assertSee('<html lang="en">', false)
        ->assertDontSee(DARK_SCHEME_SCRIPT, false);
});

it('follows the browser from the first response when the theme is system', function (): void {
    // A new user holds the default theme before any read from the database.
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertSee('<html lang="en">', false)
        ->assertSee(DARK_SCHEME_SCRIPT, false);
});

it('follows the browser from the first response for guests', function (): void {
    $this->get(route('home'))
        ->assertSee('<html lang="en">', false)
        ->assertSee(DARK_SCHEME_SCRIPT, false);
});

it('sets the page background for both themes before the first paint', function (): void {
    $this->get(route('home'))
        ->assertSeeInOrder(['<style', 'html {', 'background-color', 'html.dark {', 'background-color', '</style>'], false);
});

it('applies the theme before loading the stylesheets, with the csp nonce', function (): void {
    $this->withVite();
    Vite::clearResolvedInstance();
    Vite::useHotFile(storage_path('framework/testing/hot'));
    file_put_contents(storage_path('framework/testing/hot'), 'http://localhost:5173');
    Vite::useCspNonce('test-nonce');

    $response = $this->get(route('home'));

    unlink(storage_path('framework/testing/hot'));

    $response->assertSeeInOrder([
        '<script nonce="test-nonce">',
        DARK_SCHEME_SCRIPT,
        '<style nonce="test-nonce">',
        'resources/css/app.css',
    ], false);
});
