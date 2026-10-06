import { Form, Head } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';

import { InputField } from '@/components/input-field';
import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { useTranslation } from '@/hooks/use-translation';
import { AuthLayout } from '@/layouts/auth-layout';
import twoFactorChallenge from '@/routes/two-factor-challenge';

export default function AuthTwoFactorChallenge() {
    const { translate } = useTranslation();
    const [usesRecoveryCode, setUsesRecoveryCode] = useState(false);
    const [code, setCode] = useState('');

    return (
        <>
            <Head title={translate('identity.two_factor_challenge.title')} />
            <div className="flex flex-col gap-2 text-center">
                <h1 className="text-2xl font-semibold">
                    {translate('identity.two_factor_challenge.title')}
                </h1>
                <p className="text-sm text-balance text-muted-foreground">
                    {usesRecoveryCode
                        ? translate('identity.two_factor_challenge.recovery_code_description')
                        : translate('identity.two_factor_challenge.code_description')}
                </p>
            </div>
            {/* A refused code is cleared, since the app shows a new one; a refused recovery code stays, to fix a typo. */}
            <Form action={twoFactorChallenge.store()} onError={() => setCode('')}>
                {({ errors, processing, clearErrors }) => (
                    <FieldGroup>
                        {usesRecoveryCode ? (
                            <InputField
                                name="recovery_code"
                                label={translate('identity.two_factor_challenge.recovery_code')}
                                error={errors.recovery_code}
                                autoComplete="off"
                                spellCheck={false}
                                required
                            />
                        ) : (
                            <Field data-invalid={errors.code !== undefined}>
                                <FieldLabel htmlFor="code" className="justify-center">
                                    {translate('identity.two_factor_challenge.code')}
                                </FieldLabel>
                                <InputOTP
                                    id="code"
                                    name="code"
                                    value={code}
                                    onChange={setCode}
                                    maxLength={6}
                                    minLength={6}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    aria-invalid={errors.code !== undefined}
                                    aria-describedby={
                                        errors.code === undefined ? undefined : 'code-error'
                                    }
                                    containerClassName="mx-auto"
                                    required
                                >
                                    <InputOTPGroup>
                                        {Array.from({ length: 6 }, (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                                aria-invalid={errors.code !== undefined}
                                                className="size-11 text-base"
                                            />
                                        ))}
                                    </InputOTPGroup>
                                </InputOTP>
                                <FieldError id="code-error" className="text-center text-balance">
                                    {errors.code}
                                </FieldError>
                            </Field>
                        )}
                        <Field>
                            <Button type="submit" size="lg" disabled={processing}>
                                {translate('identity.two_factor_challenge.submit')}
                            </Button>
                            <Button
                                type="button"
                                variant="link"
                                onClick={() => {
                                    clearErrors();
                                    setCode('');
                                    setUsesRecoveryCode(!usesRecoveryCode);
                                }}
                            >
                                {usesRecoveryCode
                                    ? translate('identity.two_factor_challenge.use_code')
                                    : translate('identity.two_factor_challenge.use_recovery_code')}
                            </Button>
                        </Field>
                    </FieldGroup>
                )}
            </Form>
        </>
    );
}

AuthTwoFactorChallenge.layout = AuthLayout;
