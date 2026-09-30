<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

arch('controllers end in Controller')->expect('App\Http\Controllers')->classes()->toHaveSuffix('Controller');

arch('form requests end in Request')->expect('App\Http\Requests')->classes()->toHaveSuffix('Request');

arch('policies end in Policy')->expect('App\Policies')->classes()->toHaveSuffix('Policy');

arch('factories end in Factory')->expect('Database\Factories')->classes()->toHaveSuffix('Factory');

arch('seeders end in Seeder')->expect('Database\Seeders')->classes()->toHaveSuffix('Seeder');

arch('service providers end in ServiceProvider')->expect('App\Providers')->classes()->toHaveSuffix('ServiceProvider');

arch('observers end in Observer')->expect('App\Observers')->classes()->toHaveSuffix('Observer');

arch('exceptions end in Exception')->expect('App\Exceptions')->classes()->toHaveSuffix('Exception');

arch('data classes end in Data')->expect('App\Data')->classes()->toHaveSuffix('Data');

arch('no class carries the suffix of a kind Laravel names without one')
    ->expect(['App', 'Database'])
    ->not->toHaveSuffix('Action')
    ->not->toHaveSuffix('Event')
    ->not->toHaveSuffix('Job')
    ->not->toHaveSuffix('Listener')
    ->not->toHaveSuffix('Mail')
    ->not->toHaveSuffix('Notification');

arch('no class carries a banned suffix')
    ->expect(['App', 'Database'])
    ->not->toHaveSuffix('Service')
    ->not->toHaveSuffix('Manager')
    ->not->toHaveSuffix('Helper')
    ->not->toHaveSuffix('Util');

it('has no folder with a banned name', function (): void {
    $folders = Finder::create()
        ->directories()
        ->in(dirname(__DIR__, 3))
        ->exclude(['node_modules', 'storage', 'vendor'])
        ->name(['Services', 'Helpers', 'Utils', 'Common', 'Misc']);

    expect(iterator_to_array($folders, preserve_keys: false))->toBeEmpty();
});
