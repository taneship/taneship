import type { Translations } from '@/lib/translation';
import type { Theme } from '@/types/theme';

export interface SharedProps {
    name: string;
    theme: Theme;
    isSidebarOpen: boolean;
    translations: Translations;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
