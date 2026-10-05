<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', fn (): Response => Inertia::render('dashboard'))->name('dashboard');
});

// Wayfinder drops the account.* routes it meets before the route named account: account.php comes first.
require __DIR__.'/account.php';
require __DIR__.'/identity.php';
