import type { Translations } from '@/lib/translation';
import type { Direction } from '@/types/direction';
import type { Theme } from '@/types/theme';
import type { User } from '@/types/user';

export interface SharedProps {
    name: string;
    direction: Direction;
    theme: Theme;
    isSidebarOpen: boolean;
    user: User | null;
    translations: Translations;
}

export type Toast = {
    type: 'success' | 'error';
    message: string;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
        flashDataType: {
            toast?: Toast;
        };
    }
}
