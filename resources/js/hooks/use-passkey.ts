import { router, usePage } from '@inertiajs/react';
import { browserSupportsWebAuthn, startRegistration, WebAuthnError } from '@simplewebauthn/browser';
import type {
    PublicKeyCredentialCreationOptionsJSON,
    RegistrationResponseJSON,
} from '@simplewebauthn/browser';
import { useState, useSyncExternalStore } from 'react';

import { useTranslation } from '@/hooks/use-translation';
import type { RouteDefinition } from '@/wayfinder';

// Whether the browser supports WebAuthn never changes while the page is open.
function subscribe() {
    return () => {};
}

// The page hands its options to a partial reload, and the server keeps their challenge in the session.
function reloadOptions(): Promise<unknown> {
    return new Promise((resolve) => {
        router.reload({
            only: ['passkeyOptions'],
            onSuccess: (page) => resolve(page.props.passkeyOptions),
            // A reload that leads to another page, such as the password confirmation, brings none.
            onFinish: () => resolve(undefined),
        });
    });
}

export function usePasskey() {
    const { translate } = useTranslation();
    const { errors } = usePage().props;
    // The server renders what nearly every browser supports: a browser without WebAuthn then hides it.
    const isSupported = useSyncExternalStore(subscribe, browserSupportsWebAuthn, () => true);
    const [isProcessing, setIsProcessing] = useState(false);
    const [failure, setFailure] = useState<string>();

    function describeFailure(error: unknown): string | undefined {
        // The user closed the browser's dialog, or another ceremony replaced this one.
        if (error instanceof Error && ['NotAllowedError', 'AbortError'].includes(error.name)) {
            return undefined;
        }

        if (
            error instanceof WebAuthnError &&
            error.code === 'ERROR_AUTHENTICATOR_PREVIOUSLY_REGISTERED'
        ) {
            return translate('identity.use_passkey.already_registered');
        }

        return translate('identity.use_passkey.not_created');
    }

    async function register(
        action: RouteDefinition<'post'>,
        data: Record<string, string>,
        onSuccess: () => void,
    ) {
        setFailure(undefined);
        setIsProcessing(true);

        const options = await reloadOptions();

        if (options === undefined) {
            setIsProcessing(false);

            return;
        }

        let credential: RegistrationResponseJSON;

        try {
            // The server wrote these options for WebAuthn: they reach the browser as they are.
            credential = await startRegistration({
                optionsJSON: options as PublicKeyCredentialCreationOptionsJSON,
            });
        } catch (error) {
            setFailure(describeFailure(error));
            setIsProcessing(false);

            return;
        }

        router.post(
            action,
            { ...data, credential: JSON.stringify(credential) },
            { preserveScroll: true, onSuccess, onFinish: () => setIsProcessing(false) },
        );
    }

    return { isSupported, isProcessing, error: failure ?? errors.credential, register };
}
