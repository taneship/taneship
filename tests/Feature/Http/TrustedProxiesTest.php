<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;

// Every request below comes from the proxy 10.0.0.1, on behalf of a client that used https.
beforeEach(function (): void {
    Route::get('/client', fn (Request $request): array => [
        'address' => $request->ip(),
        'isSecure' => $request->isSecure(),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.7', 'X-Forwarded-Proto' => 'https']);
});

it('trusts no proxy by default', function (): void {
    $this->get('/client')->assertExactJson(['address' => '10.0.0.1', 'isSecure' => false]);
});

it('reads the address and the scheme of the client from a trusted proxy', function (string $proxies): void {
    config(['trustedproxy.proxies' => $proxies]);

    $this->get('/client')->assertExactJson(['address' => '203.0.113.7', 'isSecure' => true]);
})->with([
    'its address' => '10.0.0.1',
    'its range' => '10.0.0.0/8',
    'one of several' => '192.0.2.1, 10.0.0.1',
    'every proxy' => '*',
]);

it('reads the proxies it trusts from the environment', function (): void {
    Env::getRepository()->set('TRUSTED_PROXIES', '10.0.0.1');

    try {
        config(['trustedproxy' => require config_path('trustedproxy.php')]);

        $this->get('/client')->assertExactJson(['address' => '203.0.113.7', 'isSecure' => true]);
    } finally {
        Env::getRepository()->clear('TRUSTED_PROXIES');
    }
});

it('ignores the headers of a proxy it does not trust', function (): void {
    config(['trustedproxy.proxies' => '192.0.2.1']);

    $this->get('/client')->assertExactJson(['address' => '10.0.0.1', 'isSecure' => false]);
});
