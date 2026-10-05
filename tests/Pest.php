<?php

declare(strict_types=1);

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Actions\EnableTwoFactorAuthentication;
use App\Actions\RegisterPasskey;
use App\Data\PasskeyRegistrationData;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Inertia\Support\Header;
use PragmaRX\Google2FA\Google2FA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Tests\SoftwareAuthenticator;
use Tests\TestCase;
use Webauthn\PublicKeyCredential;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        // Feature tests assert the Inertia response. They need neither built assets
        // nor the HTML of a server-side rendering server that happens to run.
        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    })
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        throw_if(
            file_exists(public_path('hot')),
            'The Vite dev server is running (public/hot exists): browser tests run against the production build. Stop composer dev first.',
        );

        // Inertia falls back to client rendering when the server render fails: here, that fails the test.
        config(['inertia.ssr.throw_on_error' => true]);

        // Pest serves every request of a test from one application, where scoped singletons outlive
        // the request. Inertia keeps a request's server render in one: forget them, as Octane does.
        app()->terminating(fn () => app()->forgetScopedInstances());
    })
    ->in('Browser');

/**
 * Reads a credential as the browser posts it, through the serializer the application binds.
 *
 * @param  array<string, mixed>  $credential
 */
function publicKeyCredential(array $credential): PublicKeyCredential
{
    return app(SerializerInterface::class)->deserialize(json_encode($credential, JSON_THROW_ON_ERROR), PublicKeyCredential::class, 'json');
}

function registerPasskeyOn(SoftwareAuthenticator $authenticator, User $user): Passkey
{
    $options = app(CreatePasskeyRegistrationOptions::class)->handle($user);

    return app(RegisterPasskey::class)->handle($user, new PasskeyRegistrationData('MacBook Pro', publicKeyCredential($authenticator->register($options))), $options);
}

function signInWithConfirmedPassword(User $user): void
{
    test()->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
}

// The next request comes from another browser: no session, and a guard that has not loaded the user.
function switchBrowser(): void
{
    session()->flush();
    Auth::forgetGuards();
}

/**
 * Asks a page for some of its props, as router.reload({ only }) does. Inertia's own reloadOnly()
 * leaves out the X-Inertia header, so the root view renders with partial props, and fails.
 *
 * @return TestResponse<Response>
 */
function partialReload(string $url, string $component, string ...$props): TestResponse
{
    return test()->get($url, [
        Header::INERTIA => 'true',
        Header::VERSION => (string) app(HandleInertiaRequests::class)->version(request()),
        Header::PARTIAL_COMPONENT => $component,
        Header::PARTIAL_ONLY => implode(',', $props),
    ]);
}

/**
 * The code an authenticator app shows for the user's secret, at the current step or the given number of steps away.
 * The step is read from now(), as the actions read it: google2fa's own clock ignores the time a test sets.
 */
function twoFactorCode(User $user, int $steps = 0): string
{
    $google2fa = app(Google2FA::class);

    return $google2fa->oathTotp((string) $user->two_factor_secret, intdiv(now()->getTimestamp(), $google2fa->getKeyRegeneration()) + $steps);
}

function pendingTwoFactorUser(): User
{
    $user = User::factory()->create();
    app(EnableTwoFactorAuthentication::class)->handle($user);

    return $user;
}
