<?php

declare(strict_types=1);

use App\Models\User;

it('seeds no user', function (): void {
    $this->seed();

    expect(User::query()->count())->toBe(0);
});
