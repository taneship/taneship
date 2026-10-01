import { Head } from '@inertiajs/react';
import { KeyRoundIcon } from 'lucide-react';

import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import type { Passkey } from '@/types/passkey';

type AccountSecurityProps = {
    passkeys: Passkey[];
};

export default function AccountSecurity({ passkeys }: AccountSecurityProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.security.title')} />
            <section className="flex flex-col gap-4">
                <div className="flex flex-col gap-1">
                    <h2 className="text-lg font-medium">
                        {translate('identity.security.passkeys.title')}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {translate('identity.security.passkeys.description')}
                    </p>
                </div>
                {passkeys.length === 0 ? (
                    <p className="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
                        {translate('identity.security.passkeys.empty')}
                    </p>
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {passkeys.map((passkey) => (
                            <li key={passkey.id} className="flex items-center gap-4 p-4">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                    <KeyRoundIcon className="size-5 text-muted-foreground" />
                                </span>
                                <div className="grid min-w-0 gap-1">
                                    <p className="truncate font-medium">{passkey.name}</p>
                                    {/* Each detail stays on one line: a narrow screen wraps between them. */}
                                    <p className="text-sm text-muted-foreground">
                                        {passkey.holder !== null && (
                                            <>
                                                <span className="whitespace-nowrap">
                                                    {passkey.holder}
                                                </span>
                                                {' · '}
                                            </>
                                        )}
                                        <span className="whitespace-nowrap">
                                            {translate('identity.security.passkeys.added', {
                                                time: passkey.added,
                                            })}
                                        </span>
                                        {' · '}
                                        <span className="whitespace-nowrap">
                                            {passkey.lastUsed === null
                                                ? translate('identity.security.passkeys.never_used')
                                                : translate(
                                                      'identity.security.passkeys.last_used',
                                                      { time: passkey.lastUsed },
                                                  )}
                                        </span>
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>
        </>
    );
}

AccountSecurity.layout = [AppLayout, AccountLayout];
