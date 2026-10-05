import { Form, Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import { logout } from '@/routes';
import account from '@/routes/account';
import verification from '@/routes/verification';

export default function AuthVerifyEmail() {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.verify_email.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">
                    {translate('identity.verify_email.title')}
                </h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {translate('identity.verify_email.description')}
                </p>
            </div>
            <Form action={verification.send()}>
                {({ processing }) => (
                    <FieldGroup>
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.verify_email.resend')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
            <Link
                href={account.profile.edit()}
                className="self-center text-sm font-medium underline underline-offset-4"
            >
                {translate('account.verify_email.change_address')}
            </Link>
            <Link
                href={logout()}
                as="button"
                className="self-center text-sm font-medium underline underline-offset-4"
            >
                {translate('identity.verify_email.sign_out')}
            </Link>
        </>
    );
}

AuthVerifyEmail.layout = AuthLayout;
