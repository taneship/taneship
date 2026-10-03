import { Form, Head, router, usePage } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { KeyRoundIcon, ShieldCheckIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { InputField } from '@/components/input-field';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { usePasskey } from '@/hooks/use-passkey';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import account from '@/routes/account';
import type { Passkey } from '@/types/passkey';
import type { TwoFactorSetup } from '@/types/two-factor-setup';

type AccountSecurityProps = {
    hasEnabledTwoFactorAuthentication: boolean;
    twoFactorSetup: TwoFactorSetup | null;
    recoveryCodes?: string[];
    passkeys: Passkey[];
};

export default function AccountSecurity({
    hasEnabledTwoFactorAuthentication,
    twoFactorSetup,
    recoveryCodes,
    passkeys,
}: AccountSecurityProps) {
    const { translate } = useTranslation();
    const { errors } = usePage().props;
    const ceremony = usePasskey();
    const [code, setCode] = useState('');
    const [isCanceling, setIsCanceling] = useState(false);
    const [name, setName] = useState('');
    const [isRemoving, setIsRemoving] = useState(false);

    function cancelTwoFactorSetup() {
        router.delete(account.twoFactorAuthentication.destroy(), {
            preserveScroll: true,
            onStart: () => setIsCanceling(true),
            onFinish: () => setIsCanceling(false),
        });
    }

    function addPasskey(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        void ceremony.register(account.passkeys.store(), { name }, () => setName(''));
    }

    // The passkey leaves the list on success, and its dialog with it.
    function removePasskey(passkey: Passkey) {
        router.delete(account.passkeys.destroy(passkey.id), {
            preserveScroll: true,
            onStart: () => setIsRemoving(true),
            onFinish: () => setIsRemoving(false),
        });
    }

    return (
        <>
            <Head title={translate('identity.security.title')} />
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
                                    onClick={() => router.reload({ only: ['recoveryCodes'] })}
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
                                    {/* Asked for alone, the new codes replace the old ones in place: a full visit would leave them out. */}
                                    <Form
                                        action={account.twoFactorAuthentication.recoveryCodes.store()}
                                        options={{ only: ['recoveryCodes'] }}
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
                                    {translate(
                                        'identity.security.two_factor_authentication.disable',
                                    )}
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
                                alt={translate(
                                    'identity.security.two_factor_authentication.qr_code',
                                )}
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
                                // The page outlives the form: left as it is, the code would fill the field of the next setup.
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
            <section aria-labelledby="passkeys" className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h2 id="passkeys" className="text-lg font-medium">
                        {translate('identity.security.passkeys.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {translate('identity.security.passkeys.description')}
                    </p>
                </div>
                {passkeys.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                        {translate('identity.security.passkeys.empty')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {passkeys.map((passkey) => (
                            <li key={passkey.id} className="flex items-center gap-4 p-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                    <KeyRoundIcon className="size-5 text-muted-foreground" />
                                </span>
                                <div className="grid min-w-0 flex-1 gap-1">
                                    <p className="truncate font-medium">{passkey.name}</p>
                                    {/* Each detail stays on one line: a narrow screen wraps between them. */}
                                    <p className="text-sm text-muted-foreground">
                                        {passkey.holder !== null && (
                                            <>
                                                <span className="whitespace-nowrap">
                                                    {passkey.holder}
                                                </span>
                                                {' · '}
                                            </>
                                        )}
                                        <span className="whitespace-nowrap">
                                            {translate('identity.security.passkeys.added', {
                                                time: passkey.added,
                                            })}
                                        </span>
                                        {' · '}
                                        <span className="whitespace-nowrap">
                                            {passkey.lastUsed === null
                                                ? translate('identity.security.passkeys.never_used')
                                                : translate(
                                                      'identity.security.passkeys.last_used',
                                                      { time: passkey.lastUsed },
                                                  )}
                                        </span>
                                    </p>
                                </div>
                                <AlertDialog>
                                    <AlertDialogTrigger
                                        render={<Button variant="ghost" size="icon" />}
                                        aria-label={translate('identity.security.passkeys.remove', {
                                            name: passkey.name,
                                        })}
                                    >
                                        <Trash2Icon />
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle className="wrap-anywhere">
                                                {translate(
                                                    'identity.security.passkeys.removal.title',
                                                    {
                                                        name: passkey.name,
                                                    },
                                                )}
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                {translate(
                                                    'identity.security.passkeys.removal.description',
                                                )}
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>
                                                {translate(
                                                    'identity.security.passkeys.removal.cancel',
                                                )}
                                            </AlertDialogCancel>
                                            <AlertDialogAction
                                                variant="destructive"
                                                disabled={isRemoving}
                                                onClick={() => removePasskey(passkey)}
                                            >
                                                {translate(
                                                    'identity.security.passkeys.removal.confirm',
                                                )}
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </li>
                        ))}
                    </ul>
                )}
                {ceremony.isSupported && (
                    <form onSubmit={addPasskey}>
                        <FieldGroup>
                            <InputField
                                name="name"
                                label={translate('identity.security.passkeys.name')}
                                error={errors.name}
                                value={name}
                                // The server trims the name. Made of spaces, it would pass as filled here,
                                // and be refused there once the authenticator holds the passkey.
                                onChange={(event) => setName(event.target.value.trimStart())}
                                autoComplete="off"
                                maxLength={255}
                                required
                            />
                            <Field orientation="horizontal">
                                <Button
                                    type="submit"
                                    disabled={ceremony.isProcessing}
                                    aria-describedby={
                                        ceremony.error === undefined
                                            ? undefined
                                            : 'credential-error'
                                    }
                                >
                                    {translate('identity.security.passkeys.add')}
                                </Button>
                            </Field>
                            <FieldError id="credential-error">{ceremony.error}</FieldError>
                        </FieldGroup>
                    </form>
                )}
            </section>
        </>
    );
}

AccountSecurity.layout = [AppLayout, AccountLayout];
