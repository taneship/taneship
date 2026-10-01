<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            SerializerInterface::class,
            fn (): SerializerInterface => new WebauthnSerializerFactory(AttestationStatementSupportManager::create())->create(),
        );

        $this->app->bind(
            AuthenticatorAttestationResponseValidator::class,
            fn (): AuthenticatorAttestationResponseValidator => AuthenticatorAttestationResponseValidator::create($this->ceremonies()->creationCeremony()),
        );

        $this->app->bind(
            AuthenticatorAssertionResponseValidator::class,
            fn (): AuthenticatorAssertionResponseValidator => AuthenticatorAssertionResponseValidator::create($this->ceremonies()->requestCeremony()),
        );
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands($this->app->isProduction());

        Password::defaults(fn (): Password => Password::min(12));
    }

    private function ceremonies(): CeremonyStepManagerFactory
    {
        $ceremonies = new CeremonyStepManagerFactory;
        $ceremonies->setAllowedOrigins([Config::string('app.url')]);

        return $ceremonies;
    }
}
