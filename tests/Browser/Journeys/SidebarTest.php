<?php

declare(strict_types=1);

use App\Models\User;

it('collapses on desktop and stays collapsed after a reload', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->assertAttribute('[data-slot="sidebar"]', 'data-state', 'expanded')
        ->assertSeeIn('[data-slot="sidebar"]', trans('foundation.sidebar.dashboard'))
        ->click('[data-slot="sidebar-trigger"]')
        ->assertAttribute('[data-slot="sidebar"]', 'data-state', 'collapsed')
        ->refresh()
        ->assertAttribute('#app', 'data-server-rendered', 'true')
        ->assertAttribute('[data-slot="sidebar"]', 'data-state', 'collapsed')
        ->assertNoSmoke();
});

it('opens as a sheet on mobile', function (): void {
    $this->actingAs(User::factory()->create());

    visit(route('dashboard'))
        ->on()->mobile()
        ->assertMissing('[data-mobile="true"]')
        ->click('[data-slot="sidebar-trigger"]')
        ->assertVisible('[data-mobile="true"]')
        ->assertSeeIn('[data-mobile="true"]', trans('foundation.sidebar.dashboard'))
        ->assertNoSmoke();
});
