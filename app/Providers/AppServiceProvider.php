<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Have I Been Pwned is a network call, which tests never make.
        Password::defaults(fn (): Password => Password::min(12)
            ->when($this->app->isProduction(), fn (Password $password): Password => $password->uncompromised()));
    }
}
