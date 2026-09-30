import { Head } from '@inertiajs/react';

import { useTranslation } from '@/hooks/use-translation';
import { AppLayout } from '@/layouts/app-layout';

export default function Dashboard() {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('foundation.dashboard.title')} />
            <h1 className="text-2xl font-semibold">{translate('foundation.dashboard.title')}</h1>
            <section className="flex flex-1 flex-col items-center justify-center gap-2 rounded-xl border border-dashed p-12 text-center">
                <h2 className="text-lg font-medium">
                    {translate('foundation.dashboard.empty.title')}
                </h2>
                <p className="text-muted-foreground">
                    {translate('foundation.dashboard.empty.description')}
                </p>
            </section>
        </>
    );
}

Dashboard.layout = AppLayout;
