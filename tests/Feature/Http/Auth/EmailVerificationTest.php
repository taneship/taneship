<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

function verificationLinkFor(User $user): string
{
    return new VerifyEmail()->toMail($user)->actionUrl;
}

it('renders the notice for an unverified user', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('auth/verify-email')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', ['name' => $user->name, 'email' => $user->email])
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true)));
});

it('sends verified users from the notice to the dashboard', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

it('sends guests from the notice to the sign-in page', function (): void {
    $this->get(route('verification.notice'))->assertRedirect(route('login'));
});

it('verifies the address and leads to the dashboard with a toast', function (): void {
    Event::fake([Verified::class]);
    $this->freezeSecond();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(verificationLinkFor($user))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.verification.verified')]);

    expect($user->refresh()->email_verified_at?->equalTo(now()))->toBeTrue();
    Event::assertDispatchedTimes(Verified::class);
});

it('leads a verified user to the dashboard without verifying again', function (): void {
    Event::fake([Verified::class]);
    $user = User::factory()->create(['email_verified_at' => now()->subDay()]);
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)
        ->get(verificationLinkFor($user))
        ->assertRedirect(route('dashboard'))
        ->assertInertiaFlashMissing('toast');

    expect($user->refresh()->email_verified_at?->equalTo($verifiedAt))->toBeTrue();
    Event::assertNotDispatched(Verified::class);
});

it('refuses the link of another user or another address', function (Closure $link): void {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get($link($user))
        ->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
})->with([
    'another user' => fn (User $user): string => verificationLinkFor(User::factory()->unverified()->create()),
    'another address' => fn (User $user): string => URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => hash('sha256', 'former@example.com'),
    ]),
]);

it('refuses a link whose signature does not match', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(verificationLinkFor($user).'0')
        ->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('accepts the link for 60 minutes', function (): void {
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    $link = verificationLinkFor($user);

    $this->travel(60)->minutes();

    $this->actingAs($user)->get($link)->assertRedirect(route('dashboard'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('refuses the link after 60 minutes', function (): void {
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    $link = verificationLinkFor($user);

    $this->travel(60)->minutes();
    $this->travel(1)->seconds();

    $this->actingAs($user)->get($link)->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('sends guests from the link to the sign-in page', function (): void {
    $user = User::factory()->unverified()->create();

    $this->get(verificationLinkFor($user))->assertRedirect(route('login'));

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('refuses a seventh request for the link within a minute', function (): void {
    $user = User::factory()->unverified()->create();
    $link = verificationLinkFor($user);

    $this->actingAs($user);

    foreach (range(1, 6) as $request) {
        $this->get($link)->assertRedirect(route('dashboard'));
    }

    $this->get($link)->assertTooManyRequests();
});
