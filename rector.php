<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/lang',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/bootstrap/cache',
        // Pest binds the closures of a dataset, and PHP refuses to rebind helper(...).
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__.'/tests',
        ],
    ])
    // Rector's default cache directory is shared by every project of the machine: --clear-cache,
    // which the gates pass, would empty it under the run of another checkout.
    ->withCache(cacheDirectory: __DIR__.'/storage/framework/cache/rector')
    ->withPhpSets(php85: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_130,
    ]);
