import { Form, Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
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
                        <Field data-invalid={errors.name !== undefined}>
                            <FieldLabel htmlFor="name">
                                {translate('identity.register.name')}
                            </FieldLabel>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                autoComplete="name"
                                required
                                aria-invalid={errors.name !== undefined}
                                aria-describedby={
                                    errors.name === undefined ? undefined : 'name-error'
                                }
                            />
                            <FieldError id="name-error">{errors.name}</FieldError>
                        </Field>
                        <Field data-invalid={errors.email !== undefined}>
                            <FieldLabel htmlFor="email">
                                {translate('identity.register.email')}
                            </FieldLabel>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="username"
                                required
                                aria-invalid={errors.email !== undefined}
                                aria-describedby={
                                    errors.email === undefined ? undefined : 'email-error'
                                }
                            />
                            <FieldError id="email-error">{errors.email}</FieldError>
                        </Field>
                        <Field data-invalid={errors.password !== undefined}>
                            <FieldLabel htmlFor="password">
                                {translate('identity.register.password')}
                            </FieldLabel>
                            <Input
                                id="password"
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                                required
                                aria-invalid={errors.password !== undefined}
                                aria-describedby={
                                    errors.password === undefined ? undefined : 'password-error'
                                }
                            />
                            <FieldError id="password-error">{errors.password}</FieldError>
                        </Field>
                        <Field>
                            <FieldLabel htmlFor="password_confirmation">
                                {translate('identity.register.password_confirmation')}
                            </FieldLabel>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                                required
                            />
                        </Field>
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
