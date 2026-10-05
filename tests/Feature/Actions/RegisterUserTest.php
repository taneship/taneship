<?php

declare(strict_types=1);

use App\Actions\RegisterUser;
use App\Data\RegistrationData;
use App\Exceptions\EmailAlreadyTakenException;
use App\Models\User;
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

it('refuses an address that another request takes first', function (): void {
    // The other request creates the account between the validation and the insert of this one.
    User::creating(function (User $user): void {
        User::withoutEvents(fn () => $user->replicate()->save());
    });

    // No query follows: PostgreSQL ends the transaction of the test at the refused insert.
    expect(fn (): User => app(RegisterUser::class)->handle(new RegistrationData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'correct horse battery',
    )))->toThrow(EmailAlreadyTakenException::class);
});
