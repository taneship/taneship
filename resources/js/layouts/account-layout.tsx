import { Link, usePage } from '@inertiajs/react';
import { cn } from 'cn';
import { useId } from 'react';
import type { ReactNode } from 'react';

import { buttonVariants } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import account from '@/routes/account';

type AccountLayoutProps = {
    children: ReactNode;
};

export function AccountLayout({ children }: AccountLayoutProps) {
    const { translate } = useTranslation();
    const { url } = usePage();
    const titleId = useId();

    const items = [
        {
            label: translate('identity.account_layout.security'),
            route: account.security.edit(),
        },
    ];

    return (
        <>
            <div className="flex flex-col gap-1">
                <h1 id={titleId} className="text-2xl font-semibold">
                    {translate('identity.account_layout.title')}
                </h1>
                <p className="text-muted-foreground">
                    {translate('identity.account_layout.description')}
                </p>
            </div>
            <div className="flex flex-col gap-6 lg:flex-row lg:gap-12">
                <nav aria-labelledby={titleId} className="lg:w-48 lg:shrink-0">
                    <ul className="flex gap-1 lg:flex-col">
                        {items.map((item) => (
                            <li key={item.route.url}>
                                <Link
                                    href={item.route}
                                    aria-current={url === item.route.url ? 'page' : undefined}
                                    className={cn(
                                        buttonVariants({ variant: 'ghost' }),
                                        'w-full justify-start aria-[current=page]:bg-muted',
                                    )}
                                >
                                    {item.label}
                                </Link>
                            </li>
                        ))}
                    </ul>
                </nav>
                <div className="flex max-w-2xl min-w-0 flex-1 flex-col gap-12">{children}</div>
            </div>
        </>
    );
}
