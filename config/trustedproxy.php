<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Behind a load balancer or a CDN, every request reaches the application
    | from the proxy: its address stands for the client's, and its scheme
    | for the one the client used. Rate limits then count every visitor
    | as one, and signed links are refused. Name the proxies to trust
    | here, as addresses or CIDR ranges separated by commas, or "*"
    | when only your proxies can reach the server. Laravel's
    | TrustProxies middleware reads this key.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
