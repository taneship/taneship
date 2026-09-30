<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        // Feature tests assert the Inertia response. They need neither built assets
        // nor the HTML of a server-side rendering server that happens to run.
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    })
    ->in('Feature');
