<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\CreatePasskeyRegistrationOptions;
use App\Data\PasskeyData;
use App\Data\TwoFactorSetupData;
use App\Http\PasskeyCeremony;
use App\Models\Passkey;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

final class SecurityController
{
    public function edit(
        #[CurrentUser] User $user,
        Google2FA $google2fa,
        CreatePasskeyRegistrationOptions $createPasskeyRegistrationOptions,
        PasskeyCeremony $passkeyCeremony,
    ): Response {
        // The browser keeps the props of a page in its history, and shows them again on Back without asking
        // the server. These hold the setup key and the recovery codes: encrypted, they are lost once sign-out clears their key.
        Inertia::encryptHistory();

        return Inertia::render('account/security', [
            'hasEnabledTwoFactorAuthentication' => $user->hasEnabledTwoFactorAuthentication(),
            // A setup is pending from its secret to its first code.
            'twoFactorSetup' => fn (): ?TwoFactorSetupData => $user->two_factor_secret === null || $user->hasEnabledTwoFactorAuthentication()
                ? null
                : new TwoFactorSetupData(
                    qrCode: new Writer(new ImageRenderer(new RendererStyle(192), new SvgImageBackEnd))->writeString(
                        $google2fa->getQRCodeUrl(config()->string('app.name'), $user->email, $user->two_factor_secret),
                    ),
                    setupKey: $user->two_factor_secret,
                ),
            // Asked for once the setup is confirmed.
            'recoveryCodes' => Inertia::optional(fn (): array => $user->hasEnabledTwoFactorAuthentication() ? $user->two_factor_recovery_codes ?? [] : []),
            // Relative times, written on the server: a date formatted in the browser
            // would depend on its time zone, and differ from the server render.
            'passkeys' => $user->passkeys()->latest()->get()->map(fn (Passkey $passkey): PasskeyData => new PasskeyData(
                id: $passkey->id,
                name: $passkey->name,
                holder: $passkey->holder,
                added: $passkey->created_at->diffForHumans(['options' => CarbonInterface::JUST_NOW]),
                lastUsed: $passkey->last_used_at?->diffForHumans(['options' => CarbonInterface::JUST_NOW]),
            )),
            // Asked for when a registration starts.
            'passkeyOptions' => Inertia::optional(fn (): mixed => $passkeyCeremony->start(
                PasskeyCeremony::REGISTRATION,
                $createPasskeyRegistrationOptions->handle($user),
            )),
        ]);
    }
}
