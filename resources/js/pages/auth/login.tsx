import { Form, Head, Link } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldGroup, FieldLabel } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import { register } from '@/routes';
import login from '@/routes/login';
import password from '@/routes/password';

export default function AuthLogin() {
    const { translate } = useTranslation();

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
                            autoComplete="username"
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
                            <Checkbox id="remember" name="remember" />
                            <FieldLabel htmlFor="remember">
                                {translate('identity.login.remember')}
                            </FieldLabel>
                        </Field>
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.login.submit')}
                            </Button>
                        </Field>
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
