import { Form, Head, Link } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import { login } from '@/routes';
import password from '@/routes/password';

export default function AuthForgotPassword() {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.forgot_password.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">
                    {translate('identity.forgot_password.title')}
                </h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.forgot_password.description')}
                </p>
            </div>
            <Form action={password.email()}>
                {({ errors, processing }) => (
                    <FieldGroup>
                        <InputField
                            name="email"
                            label={translate('identity.forgot_password.email')}
                            error={errors.email}
                            type="email"
                            autoComplete="username"
                            required
                        />
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.forgot_password.submit')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
            <p className="text-center text-sm text-muted-foreground">
                {translate('identity.forgot_password.remembered')}{' '}
                <Link
                    href={login()}
                    className="font-medium text-foreground underline underline-offset-4"
                >
                    {translate('identity.forgot_password.sign_in')}
                </Link>
            </p>
        </>
    );
}

AuthForgotPassword.layout = AuthLayout;
