import { cn } from 'cn';
import { EyeIcon, EyeOffIcon } from 'lucide-react';
import { useEffect, useRef, useState, type ComponentProps } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslation } from '@/hooks/use-translation';

type PasswordInputProps = Omit<ComponentProps<typeof Input>, 'type' | 'ref'>;

export function PasswordInput({ className, ...props }: PasswordInputProps) {
    const { translate } = useTranslation();
    const inputRef = useRef<HTMLInputElement>(null);
    const [isVisible, setIsVisible] = useState(false);

    // Password managers recognize a password by the type of its field: still shown as text when
    // the form is sent, it would not be offered for saving.
    useEffect(() => {
        const form = inputRef.current?.form;
        const hide = () => setIsVisible(false);

        form?.addEventListener('submit', hide);

        return () => form?.removeEventListener('submit', hide);
    }, []);

    return (
        <div className="relative">
            <Input
                ref={inputRef}
                type={isVisible ? 'text' : 'password'}
                className={cn('pe-9', className)}
                {...props}
            />
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                className="absolute end-0.5 top-0.5 text-muted-foreground"
                aria-label={translate('identity.password_input.show')}
                aria-controls={props.id}
                aria-pressed={isVisible}
                onClick={() => setIsVisible((wasVisible) => !wasVisible)}
            >
                {isVisible ? <EyeOffIcon /> : <EyeIcon />}
            </Button>
        </div>
    );
}
