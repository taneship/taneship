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

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        throw_if(
            file_exists(public_path('hot')),
            'The Vite dev server is running (public/hot exists): browser tests run against the production build. Stop composer dev first.',
        );

        // Inertia falls back to client rendering when the server render fails: here, that fails the test.
        config(['inertia.ssr.throw_on_error' => true]);

        // Pest serves every request of a test from one application, where scoped singletons outlive
        // the request. Inertia keeps a request's server render in one: forget them, as Octane does.
        app()->terminating(fn () => app()->forgetScopedInstances());
    })
    ->in('Browser');
