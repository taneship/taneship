import { Head } from '@inertiajs/react';

import { DeleteAccountSection } from '@/components/delete-account-section';
import { PasskeysSection } from '@/components/passkeys-section';
import { PasswordSection } from '@/components/password-section';
import { TwoFactorAuthenticationSection } from '@/components/two-factor-authentication-section';
import { useTranslation } from '@/hooks/use-translation';
import { AccountLayout } from '@/layouts/account-layout';
import { AppLayout } from '@/layouts/app-layout';
import type { Passkey } from '@/types/passkey';
import type { TwoFactorSetup } from '@/types/two-factor-setup';

type AccountSecurityProps = {
    passwordRules: string;
    hasEnabledTwoFactorAuthentication: boolean;
    twoFactorSetup: TwoFactorSetup | null;
    recoveryCodes?: string[];
    passkeys: Passkey[];
};

export default function AccountSecurity({
    passwordRules,
    hasEnabledTwoFactorAuthentication,
    twoFactorSetup,
    recoveryCodes,
    passkeys,
}: AccountSecurityProps) {
    const { translate } = useTranslation();

    return (
        <>
            <Head title={translate('identity.security.title')} />
            <PasswordSection passwordRules={passwordRules} />
            <TwoFactorAuthenticationSection
                hasEnabledTwoFactorAuthentication={hasEnabledTwoFactorAuthentication}
                twoFactorSetup={twoFactorSetup}
                recoveryCodes={recoveryCodes}
            />
            <PasskeysSection passkeys={passkeys} />
            <DeleteAccountSection />
        </>
    );
}

AccountSecurity.layout = [AppLayout, AccountLayout];
