import { Head, usePage } from '@inertiajs/react';

import { useTranslation } from '@/hooks/use-translation';

export default function Welcome() {
    const { name } = usePage().props;
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('foundation.welcome.title')}>
                <meta name="description" content={translate('foundation.welcome.description')} />
            </Head>
            <main className="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center">
                <h1 className="text-4xl font-semibold">{name}</h1>
                <p className="text-lg text-muted-foreground">
                    {translate('foundation.welcome.description')}
                </p>
            </main>
        </>
    );
}
