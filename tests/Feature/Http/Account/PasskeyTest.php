<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use Symfony\Component\Serializer\SerializerInterface;
use Tests\SoftwareAuthenticator;
use Webauthn\PublicKeyCredentialCreationOptions;

// The browser asks the security page for the options of a ceremony, then signs them.
function registrationOptions(): PublicKeyCredentialCreationOptions
{
    $options = partialReload(route('account.security.edit'), 'account/security', 'passkeyOptions')->json('props.passkeyOptions');

    return app(SerializerInterface::class)->deserialize(json_encode($options, JSON_THROW_ON_ERROR), PublicKeyCredentialCreationOptions::class, 'json');
}

function signInWithConfirmedPassword(User $user): void
{
    test()->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
}

it('registers the passkey under its name, with a toast', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    $credential = new SoftwareAuthenticator('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')->register(registrationOptions());

    $this->from(route('account.security.edit'))
        ->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => json_encode($credential)])
        ->assertRedirect(route('account.security.edit'))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('passkeys.registration_options')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.passkeys.added')]);

    $passkey = $user->passkeys()->sole();

    expect($passkey->name)->toBe('MacBook Pro')
        ->and($passkey->credential_id)->toBe($credential['id'])
        ->and($passkey->holder)->toBe('Apple Passwords');
});

it('serves each ceremony once', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    $options = registrationOptions();
    $this->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => json_encode(new SoftwareAuthenticator()->register($options))]);

    $this->post(route('account.passkeys.store'), ['name' => 'Work laptop', 'credential' => json_encode(new SoftwareAuthenticator()->register($options))])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    expect($user->passkeys()->pluck('name')->all())->toBe(['MacBook Pro']);
});

it('refuses a ceremony of earlier options', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    $credential = new SoftwareAuthenticator()->register(registrationOptions());
    registrationOptions();

    $this->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => json_encode($credential)])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    expect($user->passkeys()->exists())->toBeFalse();
});

it('refuses a failed ceremony, and asks for a new one', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    $credential = new SoftwareAuthenticator()->register(registrationOptions(), 'https://evil.example');

    $this->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => json_encode($credential)])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')])
        ->assertSessionMissing('passkeys.registration_options');

    expect($user->passkeys()->exists())->toBeFalse();
});

it('refuses a credential already registered', function (): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    $credential = json_encode(new SoftwareAuthenticator()->register(registrationOptions()));
    $options = session('passkeys.registration_options');
    $this->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => $credential]);

    // The options are put back, as when two requests carry the same ceremony at once.
    session(['passkeys.registration_options' => $options]);

    $this->post(route('account.passkeys.store'), ['name' => 'Work laptop', 'credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    expect($user->passkeys()->pluck('name')->all())->toBe(['MacBook Pro']);
});

it('refuses a credential it cannot read', function (string $credential): void {
    $user = User::factory()->create();
    signInWithConfirmedPassword($user);
    registrationOptions();

    $this->post(route('account.passkeys.store'), ['name' => 'MacBook Pro', 'credential' => $credential])
        ->assertSessionHasErrors(['credential' => trans('identity.passkeys.invalid')]);

    expect($user->passkeys()->exists())->toBeFalse();
})->with([
    'not json' => 'a passkey',
    'no id' => '{"rawId": "AAAA", "type": "public-key", "response": {}}',
    'no response' => '{"id": "AAAA", "rawId": "AAAA", "type": "public-key"}',
]);

it('validates the form', function (array $input, array $errors): void {
    signInWithConfirmedPassword(User::factory()->create());

    $this->post(route('account.passkeys.store'), $input)->assertSessionHasErrors($errors);
})->with([
    'empty' => [[], ['name', 'credential']],
    'a name too long' => [['name' => str_repeat('a', 256), 'credential' => '{}'], ['name']],
]);

it('removes the passkey, with a toast', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user)->create();
    signInWithConfirmedPassword($user);

    $this->from(route('account.security.edit'))
        ->delete(route('account.passkeys.destroy', $passkey))
        ->assertRedirect(route('account.security.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => trans('identity.passkeys.removed')]);

    $this->assertModelMissing($passkey);
});

it('refuses to remove the passkey of another user', function (): void {
    $passkey = Passkey::factory()->create();
    signInWithConfirmedPassword(User::factory()->create());

    $this->delete(route('account.passkeys.destroy', $passkey))->assertForbidden();

    $this->assertModelExists($passkey);
});

dataset('passkey requests', [
    'registration' => ['post', fn (): string => route('account.passkeys.store')],
    'removal' => ['delete', fn (): string => route('account.passkeys.destroy', Passkey::factory()->create())],
]);

it('asks for the password first', function (string $method, string $url): void {
    $this->actingAs(User::factory()->create())
        ->{$method}($url)
        ->assertRedirect(route('password.confirm'));
})->with('passkey requests');

it('sends unverified users to the verification notice', function (string $method, string $url): void {
    signInWithConfirmedPassword(User::factory()->unverified()->create());

    $this->{$method}($url)->assertRedirect(route('verification.notice'));
})->with('passkey requests');

it('sends guests to the sign-in page', function (string $method, string $url): void {
    $this->{$method}($url)->assertRedirect(route('login'));
})->with('passkey requests');
