import { Form, Head } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import password from '@/routes/password';

export default function AuthConfirmPassword() {
    const { translate } = useTranslation();

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
                    </FieldGroup>
                )}
            </Form>
        </>
    );
}

AuthConfirmPassword.layout = AuthLayout;
