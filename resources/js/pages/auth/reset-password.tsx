import { Form, Head } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import password from '@/routes/password';

type AuthResetPasswordProps = {
    token: string;
    email: string;
    passwordRules: string;
};

export default function AuthResetPassword({ token, email, passwordRules }: AuthResetPasswordProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.reset_password.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">
                    {translate('identity.reset_password.title')}
                </h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.reset_password.description')}
                </p>
            </div>
            <Form action={password.update()} resetOnError={['password', 'password_confirmation']}>
                {({ errors, processing }) => (
                    <FieldGroup>
                        <input type="hidden" name="token" value={token} />
                        <InputField
                            name="email"
                            label={translate('identity.reset_password.email')}
                            error={errors.email}
                            type="email"
                            autoComplete="username"
                            defaultValue={email}
                            required
                        />
                        <InputField
                            name="password"
                            label={translate('identity.reset_password.password')}
                            error={errors.password}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <InputField
                            name="password_confirmation"
                            label={translate('identity.reset_password.password_confirmation')}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.reset_password.submit')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
        </>
    );
}

AuthResetPassword.layout = AuthLayout;
