<?php

declare(strict_types=1);

use App\Http\Controllers\Account\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::redirect('/account', '/account/profile')->name('account');
    Route::get('/account/profile', [ProfileController::class, 'edit'])->name('account.profile.edit');
    Route::put('/account/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:6,1,account.profile.update')
        ->name('account.profile.update');
});
