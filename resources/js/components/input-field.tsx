import type { ComponentProps, ReactNode } from 'react';

import { PasswordInput } from '@/components/password-input';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

type InputFieldProps = Omit<ComponentProps<typeof Input>, 'id' | 'name'> & {
    name: string;
    label: string;
    labelAction?: ReactNode;
    error?: string | undefined;
};

// The name is also the id of the input, and names the error that describes it.
// A password field gets a button that shows what is typed.
export function InputField({ name, label, labelAction, error, type, ...props }: InputFieldProps) {
    const isInvalid = error !== undefined;
    const errorId = `${name}-error`;

    return (
        <Field data-invalid={isInvalid}>
            {labelAction === undefined ? (
                <FieldLabel htmlFor={name}>{label}</FieldLabel>
            ) : (
                <div className="flex items-center">
                    <FieldLabel htmlFor={name}>{label}</FieldLabel>
                    {labelAction}
                </div>
            )}
            {type === 'password' ? (
                <PasswordInput
                    id={name}
                    name={name}
                    aria-invalid={isInvalid}
                    aria-describedby={isInvalid ? errorId : undefined}
                    {...props}
                />
            ) : (
                <Input
                    id={name}
                    name={name}
                    type={type}
                    aria-invalid={isInvalid}
                    aria-describedby={isInvalid ? errorId : undefined}
                    {...props}
                />
            )}
            <FieldError id={errorId}>{error}</FieldError>
        </Field>
    );
}
