import type { Theme } from '@/types/theme';

export type ThemeRoot = {
    classList: Pick<DOMTokenList, 'toggle'>;
};

export type ColorScheme = {
    readonly matches: boolean;
    addEventListener(type: 'change', listener: () => void): void;
    removeEventListener(type: 'change', listener: () => void): void;
};

// Listing every theme makes the compiler flag a theme added to the type but not here.
const themes: Record<Theme, true> = { light: true, dark: true, system: true };

export function isTheme(value: unknown): value is Theme {
    return typeof value === 'string' && Object.hasOwn(themes, value);
}

// Applies the theme to the root element and, while the theme is system, follows the browser's
// color scheme. The returned function stops following it.
export function followTheme(theme: Theme, root: ThemeRoot, scheme: ColorScheme): () => void {
    const apply = () => {
        root.classList.toggle('dark', theme === 'dark' || (theme === 'system' && scheme.matches));
    };

    apply();

    if (theme !== 'system') {
        return () => {};
    }

    scheme.addEventListener('change', apply);

    return () => {
        scheme.removeEventListener('change', apply);
    };
}
