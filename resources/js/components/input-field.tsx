import type { ComponentProps, ReactNode } from 'react';

import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';

type InputFieldProps = Omit<ComponentProps<typeof Input>, 'id' | 'name'> & {
    name: string;
    label: string;
    labelAction?: ReactNode;
    error?: string | undefined;
};

// The name is also the id of the input, and names the error that describes it.
export function InputField({ name, label, labelAction, error, ...props }: InputFieldProps) {
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
            <Input
                id={name}
                name={name}
                aria-invalid={isInvalid}
                aria-describedby={isInvalid ? errorId : undefined}
                {...props}
            />
            <FieldError id={errorId}>{error}</FieldError>
        </Field>
    );
}
