import { Form, Head, Link } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import { login } from '@/routes';
import register from '@/routes/register';

type AuthRegisterProps = {
    passwordRules: string;
};

export default function AuthRegister({ passwordRules }: AuthRegisterProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.register.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">{translate('identity.register.title')}</h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.register.description')}
                </p>
            </div>
            <Form action={register.store()} resetOnError={['password', 'password_confirmation']}>
                {({ errors, processing }) => (
                    <FieldGroup>
                        <InputField
                            name="name"
                            label={translate('identity.register.name')}
                            error={errors.name}
                            type="text"
                            autoComplete="name"
                            required
                        />
                        <InputField
                            name="email"
                            label={translate('identity.register.email')}
                            error={errors.email}
                            type="email"
                            autoComplete="username"
                            required
                        />
                        <InputField
                            name="password"
                            label={translate('identity.register.password')}
                            error={errors.password}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <InputField
                            name="password_confirmation"
                            label={translate('identity.register.password_confirmation')}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.register.submit')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
            <p className="text-center text-sm text-muted-foreground">
                {translate('identity.register.has_account')}{' '}
                <Link
                    href={login()}
                    className="font-medium text-foreground underline underline-offset-4"
                >
                    {translate('identity.register.sign_in')}
                </Link>
            </p>
        </>
    );
}

AuthRegister.layout = AuthLayout;
