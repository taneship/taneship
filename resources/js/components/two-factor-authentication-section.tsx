import { Form, router, usePage } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { ShieldCheckIcon } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { useTranslation } from '@/hooks/use-translation';
import account from '@/routes/account';
import type { TwoFactorSetup } from '@/types/two-factor-setup';

// Asked for together: a page left open in another tab then shows the state two-factor authentication is in now.
const twoFactorAuthenticationProps = [
    'recoveryCodes',
    'hasEnabledTwoFactorAuthentication',
    'twoFactorSetup',
];

type TwoFactorAuthenticationSectionProps = {
    hasEnabledTwoFactorAuthentication: boolean;
    twoFactorSetup: TwoFactorSetup | null;
    recoveryCodes?: string[];
};

export function TwoFactorAuthenticationSection({
    hasEnabledTwoFactorAuthentication,
    twoFactorSetup,
    recoveryCodes,
}: TwoFactorAuthenticationSectionProps) {
    const { translate } = useTranslation();
    const { errors } = usePage().props;
    const [code, setCode] = useState('');
    const [isCanceling, setIsCanceling] = useState(false);

    function cancelTwoFactorSetup() {
        router.delete(account.twoFactorAuthentication.destroy(), {
            preserveScroll: true,
            onStart: () => setIsCanceling(true),
            onFinish: () => setIsCanceling(false),
        });
    }

    return (
        <section aria-labelledby="two-factor-authentication" className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
                <h2 id="two-factor-authentication" className="text-lg font-medium">
                    {translate('identity.security.two_factor_authentication.title')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {translate('identity.security.two_factor_authentication.description')}
                </p>
            </div>
            {hasEnabledTwoFactorAuthentication ? (
                <>
                    <p className="flex items-center gap-2 text-sm">
                        <ShieldCheckIcon className="size-4 shrink-0 text-muted-foreground" />
                        {translate('identity.security.two_factor_authentication.enabled')}
                    </p>
                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                        <div className="flex flex-col gap-1">
                            <h3 className="font-medium">
                                {translate(
                                    'identity.security.two_factor_authentication.recovery_codes.title',
                                )}
                            </h3>
                            <p className="text-sm text-muted-foreground">
                                {translate(
                                    'identity.security.two_factor_authentication.recovery_codes.description',
                                )}
                            </p>
                        </div>
                        {recoveryCodes === undefined ? (
                            <Button
                                variant="outline"
                                className="self-start"
                                onClick={() =>
                                    router.reload({ only: twoFactorAuthenticationProps })
                                }
                            >
                                {translate(
                                    'identity.security.two_factor_authentication.recovery_codes.show',
                                )}
                            </Button>
                        ) : (
                            <>
                                <ul className="grid gap-1 font-mono text-sm sm:grid-cols-2">
                                    {recoveryCodes.map((recoveryCode) => (
                                        <li key={recoveryCode}>{recoveryCode}</li>
                                    ))}
                                </ul>
                                {/* Asked for by name, the new codes replace the old ones in place: a full visit would leave them out. */}
                                <Form
                                    action={account.twoFactorAuthentication.recoveryCodes.store()}
                                    options={{ only: twoFactorAuthenticationProps }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            disabled={processing}
                                        >
                                            {translate(
                                                'identity.security.two_factor_authentication.recovery_codes.regenerate',
                                            )}
                                        </Button>
                                    )}
                                </Form>
                            </>
                        )}
                    </div>
                    <Form action={account.twoFactorAuthentication.destroy()}>
                        {({ processing }) => (
                            <Button type="submit" variant="destructive" disabled={processing}>
                                {translate('identity.security.two_factor_authentication.disable')}
                            </Button>
                        )}
                    </Form>
                </>
            ) : twoFactorSetup === null ? (
                <Form action={account.twoFactorAuthentication.store()}>
                    {({ processing }) => (
                        <Button type="submit" disabled={processing}>
                            {translate('identity.security.two_factor_authentication.enable')}
                        </Button>
                    )}
                </Form>
            ) : (
                <>
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
                        {/* The server draws the QR code on a white background, which scanners need in dark mode too. */}
                        <img
                            src={`data:image/svg+xml,${encodeURIComponent(twoFactorSetup.qrCode)}`}
                            alt={translate('identity.security.two_factor_authentication.qr_code')}
                            width={192}
                            height={192}
                            className="shrink-0 rounded-lg border"
                        />
                        <div className="flex min-w-0 flex-col gap-2">
                            <p className="text-sm">
                                {translate('identity.security.two_factor_authentication.scan')}
                            </p>
                            <code className="rounded-md bg-muted px-2 py-1 font-mono text-sm break-all select-all">
                                {twoFactorSetup.setupKey}
                            </code>
                        </div>
                    </div>
                    <Form
                        action={account.twoFactorAuthentication.update()}
                        onError={() => setCode('')}
                        onSuccess={() => {
                            // The section outlives the form: left as it is, the code would fill the field of the next setup.
                            setCode('');
                            // Recovery codes are shown once the setup is confirmed, and only then asked for.
                            router.reload({ only: ['recoveryCodes'] });
                        }}
                    >
                        {({ processing }) => (
                            <FieldGroup>
                                <Field data-invalid={errors.code !== undefined}>
                                    <FieldLabel htmlFor="code">
                                        {translate(
                                            'identity.security.two_factor_authentication.code',
                                        )}
                                    </FieldLabel>
                                    <InputOTP
                                        id="code"
                                        name="code"
                                        value={code}
                                        onChange={setCode}
                                        maxLength={6}
                                        minLength={6}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        aria-invalid={errors.code !== undefined}
                                        aria-describedby={
                                            errors.code === undefined ? undefined : 'code-error'
                                        }
                                        required
                                    >
                                        <InputOTPGroup>
                                            {Array.from({ length: 6 }, (_, index) => (
                                                <InputOTPSlot
                                                    key={index}
                                                    index={index}
                                                    aria-invalid={errors.code !== undefined}
                                                />
                                            ))}
                                        </InputOTPGroup>
                                    </InputOTP>
                                    <FieldError id="code-error">{errors.code}</FieldError>
                                </Field>
                                <Field orientation="horizontal">
                                    <Button type="submit" disabled={processing}>
                                        {translate(
                                            'identity.security.two_factor_authentication.confirm',
                                        )}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={isCanceling}
                                        onClick={cancelTwoFactorSetup}
                                    >
                                        {translate(
                                            'identity.security.two_factor_authentication.cancel',
                                        )}
                                    </Button>
                                </Field>
                            </FieldGroup>
                        )}
                    </Form>
                </>
            )}
        </section>
    );
}
