import { usePage } from '@inertiajs/react';

import { translate, translateChoice } from '@/lib/translation';
import type { Replacements } from '@/lib/translation';

export function useTranslation() {
    const { translations } = usePage().props;

    return {
        translate: (key: string, replacements?: Replacements): string =>
            translate(translations, key, replacements),
        translateChoice: (key: string, count: number, replacements?: Replacements): string =>
            translateChoice(translations, key, count, replacements),
    };
}
