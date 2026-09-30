import { Head, Link } from '@inertiajs/react';

import { useTranslation } from '@/hooks/use-translation';
import { home } from '@/routes';

type HttpErrorProps = {
    status: number;
};

export default function HttpError({ status }: HttpErrorProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate(`foundation.http_error.${status}.title`)} />
            <main className="flex min-h-screen flex-col items-center justify-center gap-4 p-6 text-center">
                <p className="text-sm font-medium text-muted-foreground">{status}</p>
                <h1 className="text-3xl font-semibold">
                    {translate(`foundation.http_error.${status}.title`)}
                </h1>
                <p className="text-muted-foreground">
                    {translate(`foundation.http_error.${status}.description`)}
                </p>
                <Link href={home()} className="font-medium underline underline-offset-4">
                    {translate('foundation.http_error.home')}
                </Link>
            </main>
        </>
    );
}
