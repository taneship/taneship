import { Head, router, usePage } from '@inertiajs/react';

import { Field, FieldLabel } from '@/components/ui/field';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import { isTheme } from '@/lib/theme';
import account from '@/routes/account';

export default function AccountPreferences() {
    const { translate } = useTranslation();
    const { theme } = usePage().props;

    // Literal keys: the translations test finds them.
    const themes = [
        { value: 'light', label: translate('account.preferences.theme.light') },
        { value: 'dark', label: translate('account.preferences.theme.dark') },
        { value: 'system', label: translate('account.preferences.theme.system') },
    ];

    // Saved as soon as it is chosen: the shared theme of the response switches the page.
    const chooseTheme = (value: unknown) => {
        if (isTheme(value)) {
            router.put(account.preferences.update(), { theme: value }, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={translate('account.preferences.title')} />
            <section aria-labelledby="theme" className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h2 id="theme" className="text-lg font-medium">
                        {translate('account.preferences.theme.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {translate('account.preferences.theme.description')}
                    </p>
                </div>
                <RadioGroup aria-labelledby="theme" value={theme} onValueChange={chooseTheme}>
                    {themes.map(({ value, label }) => (
                        <Field key={value} orientation="horizontal">
                            <RadioGroupItem id={`theme-${value}`} value={value} />
                            <FieldLabel htmlFor={`theme-${value}`} className="font-normal">
                                {label}
                            </FieldLabel>
                        </Field>
                    ))}
                </RadioGroup>
            </section>
        </>
    );
}

AccountPreferences.layout = [AppLayout, AccountLayout];
