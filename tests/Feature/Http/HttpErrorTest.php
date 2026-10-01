<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

function routeFailingWith(int $status): string
{
    Route::middleware('web')->get('/failing-'.$status, fn () => abort($status));

    return '/failing-'.$status;
}

it('renders an http error as a page, with the shared props', function (int $status): void {
    $this->get(routeFailingWith($status))
        ->assertStatus($status)
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('http-error')
            ->where('status', $status)
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', null)
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
})->with([403, 404, 429, 503]);

it('renders a url that matches no route as a page', function (): void {
    $this->get('/missing')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('http-error')
            ->where('status', 404)
            ->where('name', config('app.name')));
});

it('renders a server error as a page when debug is off', function (): void {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/broken', fn () => throw new RuntimeException('Broken.'));

    $this->get('/broken')
        ->assertInternalServerError()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('http-error')
            ->where('status', 500));
});

it('leaves a server error to laravel when debug is on', function (): void {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/broken', fn () => throw new RuntimeException('Broken.'));

    $this->get('/broken')
        ->assertInternalServerError()
        ->assertDontSee('data-page="app"', false);
});

it('sends an expired page back with a toast', function (): void {
    Route::middleware('web')->post('/expired', fn () => abort(419));

    $this->from(route('home'))
        ->post('/expired')
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => trans('foundation.http_error.expired')]);
});

it('leaves errors to laravel for json requests', function (): void {
    $this->getJson('/missing')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('has a title and a description for every status it renders', function (int $status): void {
    expect(trans()->has("foundation.http_error.{$status}.title"))->toBeTrue()
        ->and(trans()->has("foundation.http_error.{$status}.description"))->toBeTrue();
})->with([403, 404, 429, 500, 503]);

it('declares the error page as html', function (): void {
    $this->get('/missing')->assertHeader('Content-Type', 'text/html; charset=utf-8');
});
