import { Form, Head } from '@inertiajs/react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import account from '@/routes/account';
import type { Profile } from '@/types/profile';

type AccountProfileProps = {
    profile: Profile;
    hasVerifiedEmail: boolean;
};

export default function AccountProfile({ profile }: AccountProfileProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('account.profile.title')} />
            <section aria-labelledby="profile" className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h2 id="profile" className="text-lg font-medium">
                        {translate('account.profile.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {translate('account.profile.description')}
                    </p>
                </div>
                <Form action={account.profile.update()} options={{ preserveScroll: true }}>
                    {({ errors, processing }) => (
                        <FieldGroup>
                            <InputField
                                name="name"
                                label={translate('account.profile.name')}
                                error={errors.name}
                                type="text"
                                autoComplete="name"
                                defaultValue={profile.name}
                                required
                            />
                            <InputField
                                name="email"
                                label={translate('account.profile.email')}
                                error={errors.email}
                                type="email"
                                autoComplete="username"
                                defaultValue={profile.email}
                                required
                            />
                            <Field orientation="horizontal">
                                <Button type="submit" disabled={processing}>
                                    {translate('account.profile.save')}
                                </Button>
                            </Field>
                        </FieldGroup>
                    )}
                </Form>
            </section>
        </>
    );
}

AccountProfile.layout = [AppLayout, AccountLayout];
