<?php

declare(strict_types=1);

it('sends the security headers with every page', function (): void {
    $this->get(route('home'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'DENY');
});

it('sends the security headers with a response that matched no route', function (): void {
    $this->get('/missing')
        ->assertNotFound()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Frame-Options', 'DENY');
});
