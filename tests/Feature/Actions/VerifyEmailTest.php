<?php

declare(strict_types=1);

use App\Actions\VerifyEmail;
use App\Exceptions\EmailAlreadyVerifiedException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

it('marks the address verified at the given time', function (): void {
    $user = User::factory()->unverified()->create();
    $verifiedAt = CarbonImmutable::parse('2026-10-01 09:30:00');

    app(VerifyEmail::class)->handle($user, $verifiedAt);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue()
        ->and($user->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
});

it('fires verified', function (): void {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    app(VerifyEmail::class)->handle($user, CarbonImmutable::now());

    Event::assertDispatchedTimes(Verified::class);
    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $user->is($event->user));
});

it('refuses an address already verified', function (): void {
    Event::fake([Verified::class]);
    $verifiedAt = CarbonImmutable::parse('2026-09-30 18:00:00');
    $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

    expect(fn () => app(VerifyEmail::class)->handle($user, CarbonImmutable::now()))
        ->toThrow(EmailAlreadyVerifiedException::class);

    expect($user->refresh()->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});
