<?php

declare(strict_types=1);

arch('every file declares strict types')
    ->expect(['App', 'Database'])
    ->toUseStrictTypes();

arch('every class is final')
    ->expect(['App', 'Database'])
    ->classes()
    ->toBeFinal();

arch('actions are final and readonly, with handle as their only public method')
    ->expect('App\Actions')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly()
    ->toHaveMethod('handle')
    ->not->toHavePublicMethodsBesides(['__construct', 'handle']);

arch('data classes are final and readonly')
    ->expect('App\Data')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('controllers have resourceful public methods only')
    ->expect('App\Http\Controllers')
    ->classes()
    ->not->toHavePublicMethodsBesides(['__construct', 'index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

arch('form requests expose toData')
    ->expect('App\Http\Requests')
    ->classes()
    ->toHaveMethod('toData');

arch('enums are backed by strings')
    ->expect('App\Enums')
    ->toBeStringBackedEnums();
