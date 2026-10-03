import { Head } from '@inertiajs/react';

import { PasskeysSection } from '@/components/passkeys-section';
import { TwoFactorAuthenticationSection } from '@/components/two-factor-authentication-section';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import type { Passkey } from '@/types/passkey';
import type { TwoFactorSetup } from '@/types/two-factor-setup';

type AccountSecurityProps = {
    hasEnabledTwoFactorAuthentication: boolean;
    twoFactorSetup: TwoFactorSetup | null;
    recoveryCodes?: string[];
    passkeys: Passkey[];
};

export default function AccountSecurity({
    hasEnabledTwoFactorAuthentication,
    twoFactorSetup,
    recoveryCodes,
    passkeys,
}: AccountSecurityProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.security.title')} />
            <TwoFactorAuthenticationSection
                hasEnabledTwoFactorAuthentication={hasEnabledTwoFactorAuthentication}
                twoFactorSetup={twoFactorSetup}
                recoveryCodes={recoveryCodes}
            />
            <PasskeysSection passkeys={passkeys} />
        </>
    );
}

AccountSecurity.layout = [AppLayout, AccountLayout];
