<?php

declare(strict_types=1);

use App\Actions\UpdateProfile;
use App\Data\ProfileData;
use App\Models\User;

it('saves the name and the address', function (): void {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Smith', email: 'jane.smith@example.com'));

    expect($user->refresh()->name)->toBe('Jane Smith')
        ->and($user->email)->toBe('jane.smith@example.com');
});

it('stores the address in lowercase', function (): void {
    $user = User::factory()->create();

    app(UpdateProfile::class)->handle($user, new ProfileData(name: 'Jane Doe', email: 'Jane@Example.COM'));

    expect($user->refresh()->email)->toBe('jane@example.com');
});
