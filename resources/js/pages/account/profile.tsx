import { Form, Head } from '@inertiajs/react';
import { Fragment, useState } from 'react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldGroup } from '@/components/ui/field';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import account from '@/routes/account';
import verification from '@/routes/verification';
import type { Profile } from '@/types/profile';

type AccountProfileProps = {
    profile: Profile;
    hasVerifiedEmail: boolean;
};

export default function AccountProfile({ profile, hasVerifiedEmail }: AccountProfileProps) {
    const { translate } = useTranslation();

    // The server trims the name and lowercases the address: after each save, new fields show what is
    // stored, not what was typed. Their key holds the stored values, so they are new as soon as these
    // change, and the number of saves, for a save that stores what was already there.
    const [saveCount, setSaveCount] = useState(0);
    const fieldsKey = [profile.name, profile.email, saveCount].join('\n');

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
                <Form
                    action={account.profile.update()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setSaveCount((count) => count + 1)}
                >
                    {({ errors, processing }) => (
                        <FieldGroup>
                            <Fragment key={fieldsKey}>
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
                            </Fragment>
                            <Field orientation="horizontal">
                                <Button type="submit" disabled={processing}>
                                    {translate('account.profile.save')}
                                </Button>
                            </Field>
                        </FieldGroup>
                    )}
                </Form>
                {!hasVerifiedEmail && (
                    <div className="flex flex-col gap-3 rounded-lg border p-4">
                        <p className="text-sm">{translate('account.profile.unverified')}</p>
                        <Form action={verification.send()} options={{ preserveScroll: true }}>
                            {({ processing }) => (
                                <Button type="submit" variant="outline" disabled={processing}>
                                    {translate('account.profile.resend')}
                                </Button>
                            )}
                        </Form>
                    </div>
                )}
            </section>
        </>
    );
}

AccountProfile.layout = [AppLayout, AccountLayout];
