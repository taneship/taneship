import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

import { home } from '@/routes';

type AuthLayoutProps = {
    children: ReactNode;
};

export function AuthLayout({ children }: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <>
            <Head>
                <meta name="robots" content="noindex" />
            </Head>
            <div className="flex min-h-svh flex-col items-center justify-center gap-8 p-6">
                <header>
                    <Link href={home()} className="text-xl font-semibold">
                        {name}
                    </Link>
                </header>
                <main className="flex w-full max-w-sm flex-col gap-6">{children}</main>
            </div>
        </>
    );
}
