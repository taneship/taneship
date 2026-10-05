<?php

declare(strict_types=1);

use App\Enums\Theme;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the preferences page for a signed-in user', function (): void {
    $this->actingAs(User::factory()->unverified()->create(['theme' => Theme::Dark]))
        ->get(route('account.preferences.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('account/preferences')
            ->where('theme', 'dark'));
});

it('sends guests to the sign-in page', function (): void {
    $this->get(route('account.preferences.edit'))->assertRedirect(route('login'));
    $this->put(route('account.preferences.update'), ['theme' => 'dark'])->assertRedirect(route('login'));
});

it('turns guests away', function (): void {
    $this->getJson(route('account.preferences.edit'))->assertUnauthorized();
});

it('saves the theme and leads back without a toast', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->from(route('account.preferences.edit'))
        ->put(route('account.preferences.update'), ['theme' => 'dark'])
        ->assertRedirect(route('account.preferences.edit'))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlashMissing('toast');

    expect($user->refresh()->theme)->toBe(Theme::Dark);
});

it('refuses a theme that does not exist', function (mixed $theme): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.preferences.update'), ['theme' => $theme])
        ->assertSessionHasErrors('theme');

    expect($user->refresh()->theme)->toBe(Theme::System);
})->with(['an unknown name' => 'sepia', 'nothing' => null, 'a list' => [['dark']]]);
