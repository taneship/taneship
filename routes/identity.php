<?php

declare(strict_types=1);

use App\Http\Controllers\Account\PasskeyController;
use App\Http\Controllers\Account\RecoveryCodeController;
use App\Http\Controllers\Account\SecurityController;
use App\Http\Controllers\Account\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\PasskeyConfirmationController;
use App\Http\Controllers\Auth\PasskeySessionController;
use App\Http\Controllers\Auth\PasswordConfirmationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

// Each limit carries the name of its route: without that prefix, Laravel counts every limited route
// of a browser, or of a user, together, and requests to one use up the limit of the others.
Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])
        ->middleware('throttle:6,1,register.store')
        ->name('register.store');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    Route::post('/login/passkey', [PasskeySessionController::class, 'store'])
        ->middleware('throttle:10,1,login.passkey.store')
        ->name('login.passkey.store');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor-challenge.create');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('two-factor-challenge.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1,password.email')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:6,1,password.update')
        ->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'create'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'store'])
        ->middleware(['signed', 'throttle:6,1,verification.verify'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1,verification.send')
        ->name('verification.send');
    Route::get('/confirm-password', [PasswordConfirmationController::class, 'create'])->name('password.confirm');
    Route::post('/confirm-password', [PasswordConfirmationController::class, 'store'])
        ->middleware('throttle:6,1,password.confirm.store')
        ->name('password.confirm.store');
    Route::post('/confirm-password/passkey', [PasskeyConfirmationController::class, 'store'])
        ->middleware('throttle:6,1,password.confirm.passkey.store')
        ->name('password.confirm.passkey.store');
});

Route::middleware(['auth', 'verified', 'password.confirm'])->group(function (): void {
    Route::get('/account/security', [SecurityController::class, 'edit'])->name('account.security.edit');
    Route::post('/account/two-factor-authentication', [TwoFactorAuthenticationController::class, 'store'])
        ->name('account.two-factor-authentication.store');
    Route::put('/account/two-factor-authentication', [TwoFactorAuthenticationController::class, 'update'])
        ->name('account.two-factor-authentication.update');
    Route::delete('/account/two-factor-authentication', [TwoFactorAuthenticationController::class, 'destroy'])
        ->name('account.two-factor-authentication.destroy');
    Route::post('/account/two-factor-authentication/recovery-codes', [RecoveryCodeController::class, 'store'])
        ->name('account.two-factor-authentication.recovery-codes.store');
    Route::post('/account/passkeys', [PasskeyController::class, 'store'])->name('account.passkeys.store');
    Route::delete('/account/passkeys/{passkey}', [PasskeyController::class, 'destroy'])
        ->can('delete', 'passkey')
        ->name('account.passkeys.destroy');
});

Route::get('/.well-known/passkey-endpoints', fn (): JsonResponse => response()->json([
    'enroll' => route('account.security.edit'),
    'manage' => route('account.security.edit'),
]))->name('well-known.passkey-endpoints');
