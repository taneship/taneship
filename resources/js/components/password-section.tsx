import { Form, usePage } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import account from '@/routes/account';

type PasswordSectionProps = {
    passwordRules: string;
};

export function PasswordSection({ passwordRules }: PasswordSectionProps) {
    const { translate } = useTranslation();
    const { user } = usePage().props;

    // The heading is not named password: that id belongs to the field of the new one.
    return (
        <section aria-labelledby="password-change" className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
                <h2 id="password-change" className="text-lg font-medium">
                    {translate('account.security.password.title')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {translate('account.security.password.description')}
                </p>
            </div>
            <Form
                action={account.password.update()}
                options={{ preserveScroll: true }}
                resetOnSuccess
                resetOnError
            >
                {({ errors, processing }) => (
                    <FieldGroup>
                        {/* For password managers, which read here whose password changes. Without a name, it is not sent. */}
                        <input
                            type="text"
                            autoComplete="username"
                            value={user?.email}
                            readOnly
                            hidden
                        />
                        <InputField
                            name="current_password"
                            label={translate('account.security.password.current_password')}
                            error={errors.current_password}
                            type="password"
                            autoComplete="current-password"
                            required
                        />
                        <InputField
                            name="password"
                            label={translate('account.security.password.password')}
                            error={errors.password}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <InputField
                            name="password_confirmation"
                            label={translate('account.security.password.password_confirmation')}
                            type="password"
                            autoComplete="new-password"
                            passwordrules={passwordRules}
                            required
                        />
                        <Field orientation="horizontal">
                            <Button type="submit" disabled={processing}>
                                {translate('account.security.password.submit')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
        </section>
    );
}
