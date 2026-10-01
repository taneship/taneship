import { Form, Head } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
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
                        <Field data-invalid={errors.email !== undefined}>
                            <FieldLabel htmlFor="email">
                                {translate('identity.reset_password.email')}
                            </FieldLabel>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                autoComplete="username"
                                defaultValue={email}
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
                                {translate('identity.reset_password.password')}
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
                                {translate('identity.reset_password.password_confirmation')}
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
