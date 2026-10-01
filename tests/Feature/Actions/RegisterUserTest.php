<?php

declare(strict_types=1);

use App\Actions\RegisterUser;
use App\Data\RegistrationData;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

it('creates the user', function (): void {
    $user = app(RegisterUser::class)->handle(new RegistrationData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'correct horse battery',
    ));

    expect($user->exists)->toBeTrue()
        ->and($user->name)->toBe('Jane Doe')
        ->and($user->email)->toBe('jane@example.com')
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('correct horse battery', $user->password))->toBeTrue();
});

it('stores the address in lowercase', function (): void {
    $user = app(RegisterUser::class)->handle(new RegistrationData(
        name: 'Jane Doe',
        email: 'Jane@Example.COM',
        password: 'correct horse battery',
    ));

    expect($user->refresh()->email)->toBe('jane@example.com');
});

it('fires registered', function (): void {
    Event::fake([Registered::class]);

    $user = app(RegisterUser::class)->handle(new RegistrationData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'correct horse battery',
    ));

    Event::assertDispatched(Registered::class, fn (Registered $event): bool => $user->is($event->user));
});
