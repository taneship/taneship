<?php

declare(strict_types=1);

use App\Models\Passkey;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia;

it('renders the security page for a user who confirmed their password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('account/security')
            ->where('name', config('app.name'))
            ->where('theme', 'system')
            ->where('isSidebarOpen', true)
            ->where('user', ['name' => $user->name, 'email' => $user->email])
            ->where('errors', [])
            ->where('translations', json_decode(File::get(lang_path('en.json')), true))
            ->where('passwordRules', 'minlength: 12;')
            ->where('hasEnabledTwoFactorAuthentication', false)
            ->where('twoFactorSetup', null)
            ->missing('recoveryCodes')
            ->where('passkeys', [])
            ->missing('passkeyOptions'));
});

it('has the browser encrypt the page in its history', function (): void {
    signInWithConfirmedPassword(User::factory()->create());

    expect($this->get(route('account.security.edit'))->inertiaPage())->toHaveKey('encryptHistory', true);
});

it('shows the QR code and the setup key while a setup is pending', function (): void {
    config(['app.name' => 'Acme Cloud']);
    $user = User::factory()->withTwoFactorAuthentication()->create(['email' => 'jane@example.com', 'two_factor_confirmed_at' => null]);
    $uri = "otpauth://totp/Acme%20Cloud:jane%40example.com?secret={$user->two_factor_secret}&issuer=Acme%20Cloud&algorithm=SHA1&digits=6&period=30";

    signInWithConfirmedPassword($user);

    $this->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('hasEnabledTwoFactorAuthentication', false)
            ->where('twoFactorSetup', [
                'qrCode' => new Writer(new ImageRenderer(new RendererStyle(192), new SvgImageBackEnd))->writeString($uri),
                'setupKey' => $user->two_factor_secret,
            ]));
});

it('hides the setup once two-factor authentication is enabled', function (): void {
    signInWithConfirmedPassword(User::factory()->withTwoFactorAuthentication()->create());

    $this->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('hasEnabledTwoFactorAuthentication', true)
            ->where('twoFactorSetup', null));
});

it('gives the recovery codes when the page asks for them', function (): void {
    $user = User::factory()->withTwoFactorAuthentication()->create();
    signInWithConfirmedPassword($user);

    partialReload(route('account.security.edit'), 'account/security', 'recoveryCodes')
        ->assertJsonMissingPath('props.passkeys')
        ->assertJsonMissingPath('props.twoFactorSetup')
        ->assertJsonPath('props.recoveryCodes', $user->two_factor_recovery_codes);
});

it('gives no recovery codes before two-factor authentication is enabled', function (): void {
    signInWithConfirmedPassword(pendingTwoFactorUser());

    partialReload(route('account.security.edit'), 'account/security', 'recoveryCodes')
        ->assertJsonPath('props.recoveryCodes', []);
});

it('lists the passkeys of the user, newest first', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();

    $oldest = Passkey::factory()->for($user)->withAaguid('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')->create([
        'name' => 'iPhone',
        'created_at' => now()->subMonths(2),
        'last_used_at' => now()->subDays(3),
    ]);
    $newest = Passkey::factory()->for($user)->withAaguid('00000000-0000-0000-0000-000000000000')->create([
        'name' => 'Work laptop',
        'created_at' => now()->subHour(),
        'last_used_at' => null,
    ]);
    Passkey::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('passkeys', [
            ['id' => $newest->id, 'name' => 'Work laptop', 'holder' => null, 'added' => '1 hour ago', 'lastUsed' => null],
            ['id' => $oldest->id, 'name' => 'iPhone', 'holder' => 'Apple Passwords', 'added' => '2 months ago', 'lastUsed' => '3 days ago'],
        ]));
});

it('writes just now for a passkey added or used a moment ago', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    Passkey::factory()->for($user)->create(['created_at' => now(), 'last_used_at' => now()]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('passkeys.0.added', 'just now')
            ->where('passkeys.0.lastUsed', 'just now'));
});

it('names holders from the configuration, so a newer list renames existing passkeys', function (): void {
    $user = User::factory()->create();
    Passkey::factory()->for($user)->withAaguid('fbfc3007-154e-4ecc-8c0b-6e020557d7bd')->create();

    config(['passkeys.authenticators' => ['fbfc3007-154e-4ecc-8c0b-6e020557d7bd' => 'iCloud Keychain']]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->where('passkeys.0.holder', 'iCloud Keychain'));
});

it('keeps the registration options in the session when the page asks for them', function (): void {
    $user = User::factory()->create();
    $passkey = Passkey::factory()->for($user)->create();

    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

    partialReload(route('account.security.edit'), 'account/security', 'passkeyOptions')
        ->assertJsonMissingPath('props.passkeys')
        ->assertJsonPath('props.passkeyOptions', json_decode(session('passkeys.registration_options'), true))
        ->assertJsonPath('props.passkeyOptions.excludeCredentials.0.id', $passkey->credential_id)
        // The browser refuses a null where WebAuthn expects a value.
        ->assertJsonMissingPath('props.passkeyOptions.authenticatorSelection.authenticatorAttachment');
});

it('renews the registration options each time the page asks for them', function (): void {
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => time()]);
    $first = partialReload(route('account.security.edit'), 'account/security', 'passkeyOptions')->json('props.passkeyOptions.challenge');

    $second = partialReload(route('account.security.edit'), 'account/security', 'passkeyOptions')->json('props.passkeyOptions.challenge');

    expect($second)->not->toBe($first)
        ->and(json_decode(session('passkeys.registration_options'), true)['challenge'])->toBe($second);
});

it('asks for the password first', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('account.security.edit'))
        ->assertRedirect(route('password.confirm'));
});

it('sends unverified users to the verification notice', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('account.security.edit'))
        ->assertRedirect(route('verification.notice'));
});

it('sends guests to the sign-in page', function (): void {
    $this->get(route('account.security.edit'))->assertRedirect(route('login'));
});
