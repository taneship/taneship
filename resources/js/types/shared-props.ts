import type { Translations } from '@/lib/translation';

export interface SharedProps {
    name: string;
    translations: Translations;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
