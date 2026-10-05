<?php

declare(strict_types=1);

use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\PreferencesController;
use App\Http\Controllers\Account\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::redirect('/account', '/account/profile')->name('account');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:6,1,account.profile.update')
        ->name('account.profile.update');
    Route::get('/account/preferences', [PreferencesController::class, 'edit'])->name('account.preferences.edit');
    Route::put('/account/preferences', [PreferencesController::class, 'update'])->name('account.preferences.update');
});

Route::middleware(['auth', 'verified', 'password.confirm'])->group(function (): void {
    Route::put('/account/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1,account.password.update')
        ->name('account.password.update');
});
