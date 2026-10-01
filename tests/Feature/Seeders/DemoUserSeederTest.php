<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Support\Facades\Auth;

it('creates the demo user', function (): void {
    $this->seed(DemoUserSeeder::class);

    $user = User::query()->sole();

    expect($user->name)->toBe('Demo User')
        ->and($user->email)->toBe('demo@example.com')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(Auth::validate(['email' => 'demo@example.com', 'password' => 'password']))->toBeTrue();
});

it('creates the demo user only once', function (): void {
    $this->seed(DemoUserSeeder::class);
    $this->seed(DemoUserSeeder::class);

    expect(User::query()->count())->toBe(1);
});
