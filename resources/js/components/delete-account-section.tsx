import { router } from '@inertiajs/react';
import { useState } from 'react';

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
import { useTranslation } from '@/hooks/use-translation';
import account from '@/routes/account';

export function DeleteAccountSection() {
    const { translate } = useTranslation();
    const [isDeleting, setIsDeleting] = useState(false);

    // The welcome page replaces this one on success, and the dialog goes with it.
    function deleteAccount() {
        router.delete(account.destroy(), {
            onStart: () => setIsDeleting(true),
            onFinish: () => setIsDeleting(false),
        });
    }

    return (
        <section aria-labelledby="delete-account" className="flex flex-col gap-4">
            <div className="flex flex-col gap-1">
                <h2 id="delete-account" className="text-lg font-medium">
                    {translate('account.security.delete_account.title')}
                </h2>
                <p className="text-sm text-muted-foreground">
                    {translate('account.security.delete_account.description')}
                </p>
            </div>
            <div>
                <AlertDialog>
                    <AlertDialogTrigger render={<Button variant="destructive" />}>
                        {translate('account.security.delete_account.delete')}
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {translate('account.security.delete_account.deletion.title')}
                            </AlertDialogTitle>
                            <AlertDialogDescription>
                                {translate('account.security.delete_account.deletion.description')}
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>
                                {translate('account.security.delete_account.deletion.cancel')}
                            </AlertDialogCancel>
                            <AlertDialogAction
                                variant="destructive"
                                disabled={isDeleting}
                                onClick={deleteAccount}
                            >
                                {translate('account.security.delete_account.deletion.confirm')}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </section>
    );
}
