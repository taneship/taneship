import { Head, router, usePage } from '@inertiajs/react';
import { KeyRoundIcon, Trash2Icon } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';

import { InputField } from '@/components/input-field';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup } from '@/components/ui/field';
import { usePasskey } from '@/hooks/use-passkey';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import account from '@/routes/account';
import type { Passkey } from '@/types/passkey';

type AccountSecurityProps = {
    passkeys: Passkey[];
};

export default function AccountSecurity({ passkeys }: AccountSecurityProps) {
    const { translate } = useTranslation();
    const { errors } = usePage().props;
    const passkey = usePasskey();
    const [name, setName] = useState('');
    const [isRemoving, setIsRemoving] = useState(false);

    function addPasskey(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        void passkey.register(account.passkeys.store(), { name }, () => setName(''));
    }

    // The passkey leaves the list on success, and its dialog with it.
    function removePasskey(passkey: Passkey) {
        router.delete(account.passkeys.destroy(passkey.id), {
            preserveScroll: true,
            onStart: () => setIsRemoving(true),
            onFinish: () => setIsRemoving(false),
        });
    }

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
                                <div className="grid min-w-0 flex-1 gap-1">
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
                                <AlertDialog>
                                    <AlertDialogTrigger
                                        render={<Button variant="ghost" size="icon" />}
                                        aria-label={translate('identity.security.passkeys.remove', {
                                            name: passkey.name,
                                        })}
                                    >
                                        <Trash2Icon />
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle className="wrap-anywhere">
                                                {translate(
                                                    'identity.security.passkeys.removal.title',
                                                    {
                                                        name: passkey.name,
                                                    },
                                                )}
                                            </AlertDialogTitle>
                                            <AlertDialogDescription>
                                                {translate(
                                                    'identity.security.passkeys.removal.description',
                                                )}
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>
                                                {translate(
                                                    'identity.security.passkeys.removal.cancel',
                                                )}
                                            </AlertDialogCancel>
                                            <AlertDialogAction
                                                variant="destructive"
                                                disabled={isRemoving}
                                                onClick={() => removePasskey(passkey)}
                                            >
                                                {translate(
                                                    'identity.security.passkeys.removal.confirm',
                                                )}
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </li>
                        ))}
                    </ul>
                )}
                {passkey.isSupported && (
                    <form onSubmit={addPasskey}>
                        <FieldGroup>
                            <InputField
                                name="name"
                                label={translate('identity.security.passkeys.name')}
                                error={errors.name}
                                value={name}
                                // The server trims the name. Made of spaces, it would pass as filled here,
                                // and be refused there once the authenticator holds the passkey.
                                onChange={(event) => setName(event.target.value.trimStart())}
                                autoComplete="off"
                                maxLength={255}
                                required
                            />
                            <Field orientation="horizontal">
                                <Button
                                    type="submit"
                                    disabled={passkey.isProcessing}
                                    aria-describedby={
                                        passkey.error === undefined ? undefined : 'credential-error'
                                    }
                                >
                                    {translate('identity.security.passkeys.add')}
                                </Button>
                            </Field>
                            <FieldError id="credential-error">{passkey.error}</FieldError>
                        </FieldGroup>
                    </form>
                )}
            </section>
        </>
    );
}

AccountSecurity.layout = [AppLayout, AccountLayout];
