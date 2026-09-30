<?php

declare(strict_types=1);

arch('actions never use the delivery layer')
    ->expect('App\Actions')
    ->not->toUse(['App\Http', 'App\Filament', 'App\Console']);
