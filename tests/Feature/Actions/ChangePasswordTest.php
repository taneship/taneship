<?php

declare(strict_types=1);

use App\Actions\ChangePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('sets the new password', function (): void {
    $user = User::factory()->create();

    app(ChangePassword::class)->handle($user, 'correct horse battery');

    expect(Hash::check('correct horse battery', $user->refresh()->password))->toBeTrue();
});

it('keeps the remember token', function (): void {
    $user = User::factory()->create(['remember_token' => 'issued-before-the-change']);

    app(ChangePassword::class)->handle($user, 'correct horse battery');

    expect($user->refresh()->remember_token)->toBe('issued-before-the-change');
});
