import { Form, Head } from '@inertiajs/react';
import { KeyRoundIcon } from 'lucide-react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldSeparator } from '@/components/ui/field';
import { usePasskey } from '@/hooks/use-passkey';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import password from '@/routes/password';

type AuthConfirmPasswordProps = {
    hasPasskeys: boolean;
};

export default function AuthConfirmPassword({ hasPasskeys }: AuthConfirmPasswordProps) {
    const { translate } = useTranslation();
    const passkey = usePasskey();

    return (
        <>
            <Head title={translate('identity.confirm_password.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">
                    {translate('identity.confirm_password.title')}
                </h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.confirm_password.description')}
                </p>
            </div>
            <Form action={password.confirm.store()} resetOnError={['password']}>
                {({ errors, processing }) => (
                    <FieldGroup>
                        <InputField
                            name="password"
                            label={translate('identity.confirm_password.password')}
                            error={errors.password}
                            type="password"
                            autoComplete="current-password"
                            required
                        />
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.confirm_password.submit')}
                            </Button>
                        </Field>
                        {hasPasskeys && passkey.isSupported && (
                            <>
                                <FieldSeparator>
                                    {translate('identity.confirm_password.or')}
                                </FieldSeparator>
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
                                                password.confirm.passkey.store(),
                                            )
                                        }
                                    >
                                        <KeyRoundIcon />
                                        {translate('identity.confirm_password.passkey')}
                                    </Button>
                                    <FieldError id="credential-error">{passkey.error}</FieldError>
                                </Field>
                            </>
                        )}
                    </FieldGroup>
                )}
            </Form>
        </>
    );
}

AuthConfirmPassword.layout = AuthLayout;
