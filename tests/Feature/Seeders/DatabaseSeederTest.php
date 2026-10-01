<?php

declare(strict_types=1);

use App\Models\User;

it('seeds the demo user outside production', function (): void {
    $this->seed();

    expect(User::query()->where('email', 'demo@example.com')->exists())->toBeTrue();
});

it('seeds no demo user in production', function (): void {
    app()->instance('env', 'production');

    // Without --force, db:seed asks for confirmation in production, and would seed nothing anyway.
    expect($this->artisan('db:seed', ['--force' => true]))->toBe(0);

    expect(User::query()->exists())->toBeFalse();
});
