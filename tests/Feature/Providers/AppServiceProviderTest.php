<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

function bootAppServiceProviderIn(string $environment): void
{
    app()->instance('env', $environment);

    app()->getProvider(AppServiceProvider::class)?->boot();
}

it('makes models strict outside production', function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

it('does not make models strict in production', function (): void {
    bootAppServiceProviderIn('production');

    expect(Model::preventsLazyLoading())->toBeFalse()
        ->and(Model::preventsSilentlyDiscardingAttributes())->toBeFalse()
        ->and(Model::preventsAccessingMissingAttributes())->toBeFalse();
});

it('uses immutable dates', function (): void {
    expect(now())->toBeInstanceOf(CarbonImmutable::class);
});

it('prohibits destructive database commands in production', function (string $command): void {
    bootAppServiceProviderIn('production');

    expect($this->artisan($command, ['--force' => true]))->toBe(1);
})->with(['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback']);
