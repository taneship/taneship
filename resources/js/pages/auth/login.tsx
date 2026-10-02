import { Form, Head, Link } from '@inertiajs/react';
import { KeyRoundIcon } from 'lucide-react';
import { useEffect, useEffectEvent, useRef } from 'react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldError, FieldGroup, FieldLabel, FieldSeparator } from '@/components/ui/field';
import { usePasskey } from '@/hooks/use-passkey';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import { register } from '@/routes';
import login from '@/routes/login';
import password from '@/routes/password';

export default function AuthLogin() {
    const { translate } = useTranslation();
    const passkey = usePasskey();
    // Read when a passkey arrives, long after the page started waiting for one from the suggestions.
    const remember = useRef(false);

    const signInData = () => ({ remember: remember.current });

    const offerPasskeys = useEffectEvent(() => {
        void passkey.authenticateFromSuggestions(login.passkey.store(), signInData);
    });

    useEffect(() => offerPasskeys(), []);

    return (
        <>
            <Head title={translate('identity.login.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">{translate('identity.login.title')}</h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.login.description')}
                </p>
            </div>
            <Form action={login.store()} resetOnError={['password']}>
                {({ errors, processing }) => (
                    <FieldGroup>
                        <InputField
                            name="email"
                            label={translate('identity.login.email')}
                            error={errors.email}
                            type="email"
                            // Saved passkeys join the suggestions of this field.
                            autoComplete="username webauthn"
                            required
                        />
                        <InputField
                            name="password"
                            label={translate('identity.login.password')}
                            labelAction={
                                <Link
                                    href={password.request()}
                                    className="ml-auto text-sm underline-offset-4 hover:underline"
                                >
                                    {translate('identity.login.forgot_password')}
                                </Link>
                            }
                            error={errors.password}
                            type="password"
                            autoComplete="current-password"
                            required
                        />
                        <Field orientation="horizontal">
                            <Checkbox
                                id="remember"
                                name="remember"
                                onCheckedChange={(checked) => {
                                    remember.current = checked;
                                }}
                            />
                            <FieldLabel htmlFor="remember">
                                {translate('identity.login.remember')}
                            </FieldLabel>
                        </Field>
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.login.submit')}
                            </Button>
                        </Field>
                        {passkey.isSupported && (
                            <>
                                <FieldSeparator>{translate('identity.login.or')}</FieldSeparator>
                                <Field>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="lg"
                                        disabled={passkey.isProcessing}
                                        aria-describedby={
                                            passkey.error === undefined
                                                ? undefined
                                                : 'credential-error'
                                        }
                                        onClick={() =>
                                            void passkey.authenticate(
                                                login.passkey.store(),
                                                signInData,
                                            )
                                        }
                                    >
                                        <KeyRoundIcon />
                                        {translate('identity.login.passkey')}
                                    </Button>
                                    <FieldError id="credential-error">{passkey.error}</FieldError>
                                </Field>
                            </>
                        )}
                    </FieldGroup>
                )}
            </Form>
            <p className="text-center text-sm text-muted-foreground">
                {translate('identity.login.no_account')}{' '}
                <Link
                    href={register()}
                    className="font-medium text-foreground underline underline-offset-4"
                >
                    {translate('identity.login.sign_up')}
                </Link>
            </p>
        </>
    );
}

AuthLogin.layout = AuthLayout;
