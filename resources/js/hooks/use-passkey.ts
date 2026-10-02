import { router, usePage } from '@inertiajs/react';
import {
    browserSupportsWebAuthn,
    browserSupportsWebAuthnAutofill,
    startAuthentication,
    startRegistration,
    WebAuthnAbortService,
    WebAuthnError,
} from '@simplewebauthn/browser';
import type {
    AuthenticationResponseJSON,
    PublicKeyCredentialCreationOptionsJSON,
    PublicKeyCredentialRequestOptionsJSON,
    RegistrationResponseJSON,
} from '@simplewebauthn/browser';
import { useEffect, useState, useSyncExternalStore } from 'react';

import { useTranslation } from '@/hooks/use-translation';
import type { RouteDefinition } from '@/wayfinder';

// Whether the browser supports WebAuthn never changes while the page is open.
function subscribe() {
    return () => {};
}

// The page hands its options to a partial reload, and the server keeps their challenge in the session.
function reloadOptions(preserveErrors: boolean): Promise<unknown> {
    return new Promise((resolve) => {
        router.reload({
            only: ['passkeyOptions'],
            preserveErrors,
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

    // Leaving the page ends the ceremony still waiting, such as a conditional request.
    useEffect(() => () => WebAuthnAbortService.cancelCeremony(), []);

    function describeFailure(error: unknown, otherwise: string): string | undefined {
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

        return otherwise;
    }

    async function register(
        action: RouteDefinition<'post'>,
        data: Record<string, string>,
        onSuccess: () => void,
    ) {
        setFailure(undefined);
        setIsProcessing(true);

        const options = await reloadOptions(false);

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
            setFailure(describeFailure(error, translate('identity.use_passkey.not_created')));
            setIsProcessing(false);

            return;
        }

        router.post(
            action,
            { ...data, credential: JSON.stringify(credential) },
            { preserveScroll: true, onSuccess, onFinish: () => setIsProcessing(false) },
        );
    }

    // The data is read once the credential arrives, which can take minutes from the suggestions.
    function postCredential(
        action: RouteDefinition<'post'>,
        data: () => Record<string, boolean>,
        credential: AuthenticationResponseJSON,
    ) {
        setIsProcessing(true);

        router.post(
            action,
            { ...data(), credential: JSON.stringify(credential) },
            { onFinish: () => setIsProcessing(false) },
        );
    }

    async function authenticate(
        action: RouteDefinition<'post'>,
        data: () => Record<string, boolean>,
    ) {
        setFailure(undefined);
        setIsProcessing(true);

        const options = await reloadOptions(false);

        if (options === undefined) {
            setIsProcessing(false);

            return;
        }

        let credential: AuthenticationResponseJSON;

        try {
            credential = await startAuthentication({
                optionsJSON: options as PublicKeyCredentialRequestOptionsJSON,
            });
        } catch (error) {
            setFailure(describeFailure(error, translate('identity.use_passkey.not_used')));
            setIsProcessing(false);

            return;
        }

        postCredential(action, data, credential);
    }

    // A conditional request: it waits, without a dialog, for a passkey picked among the suggestions of the
    // field whose autocomplete ends with webauthn. The user asked for nothing yet, so it fails in silence,
    // leaves the processing state to the dialog that may replace it, and keeps the errors on screen.
    async function authenticateFromSuggestions(
        action: RouteDefinition<'post'>,
        data: () => Record<string, boolean>,
    ) {
        if (!(await browserSupportsWebAuthnAutofill())) {
            return;
        }

        const options = await reloadOptions(true);

        if (options === undefined) {
            return;
        }

        let credential: AuthenticationResponseJSON;

        try {
            credential = await startAuthentication({
                optionsJSON: options as PublicKeyCredentialRequestOptionsJSON,
                useBrowserAutofill: true,
            });
        } catch {
            return;
        }

        postCredential(action, data, credential);
    }

    return {
        isSupported,
        isProcessing,
        error: failure ?? errors.credential,
        register,
        authenticate,
        authenticateFromSuggestions,
    };
}
