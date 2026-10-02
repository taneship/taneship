<?php

declare(strict_types=1);

it('points password managers to the security page', function (): void {
    $this->get('/.well-known/passkey-endpoints')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson([
            'enroll' => url('/account/security'),
            'manage' => url('/account/security'),
        ]);
});
