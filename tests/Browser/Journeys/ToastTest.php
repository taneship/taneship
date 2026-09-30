<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

it('shows a flashed toast', function (string $type): void {
    Route::middleware('web')->get('/toast', function () use ($type): Response {
        Inertia::flash('toast', ['type' => $type, 'message' => 'The toast was shown.']);

        return Inertia::render('welcome');
    });

    visit('/toast')
        ->assertSee('The toast was shown.')
        ->assertNoSmoke();
})->with(['success', 'error']);
