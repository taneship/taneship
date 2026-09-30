import { describe, expect, it } from 'vite-plus/test';

import { followTheme, isTheme } from './theme';
import type { ColorScheme, ThemeRoot } from './theme';

function fakeRoot(): ThemeRoot & { classes: Set<string> } {
    const classes = new Set<string>();

    return {
        classes,
        classList: {
            toggle: (token: string, force?: boolean) => {
                if (force === true) {
                    classes.add(token);
                } else {
                    classes.delete(token);
                }

                return force === true;
            },
        },
    };
}

function fakeScheme(prefersDark: boolean): ColorScheme & {
    prefersDark: (value: boolean) => void;
    listeners: Set<() => void>;
} {
    const listeners = new Set<() => void>();
    const scheme = {
        matches: prefersDark,
        listeners,
        addEventListener: (_type: 'change', listener: () => void) => {
            listeners.add(listener);
        },
        removeEventListener: (_type: 'change', listener: () => void) => {
            listeners.delete(listener);
        },
        prefersDark: (value: boolean) => {
            scheme.matches = value;
            listeners.forEach((listener) => {
                listener();
            });
        },
    };

    return scheme;
}

describe('followTheme', () => {
    it('applies the dark theme', () => {
        const root = fakeRoot();

        followTheme('dark', root, fakeScheme(false));

        expect(root.classes.has('dark')).toBe(true);
    });

    it('applies the light theme', () => {
        const root = fakeRoot();
        root.classes.add('dark');

        followTheme('light', root, fakeScheme(true));

        expect(root.classes.has('dark')).toBe(false);
    });

    it('applies the browser color scheme when the theme is system', () => {
        const darkRoot = fakeRoot();
        const lightRoot = fakeRoot();

        followTheme('system', darkRoot, fakeScheme(true));
        followTheme('system', lightRoot, fakeScheme(false));

        expect(darkRoot.classes.has('dark')).toBe(true);
        expect(lightRoot.classes.has('dark')).toBe(false);
    });

    it('follows changes of the browser color scheme when the theme is system', () => {
        const root = fakeRoot();
        const scheme = fakeScheme(false);

        followTheme('system', root, scheme);
        scheme.prefersDark(true);

        expect(root.classes.has('dark')).toBe(true);

        scheme.prefersDark(false);

        expect(root.classes.has('dark')).toBe(false);
    });

    it('ignores the browser color scheme when the theme is set', () => {
        const scheme = fakeScheme(false);

        followTheme('light', fakeRoot(), scheme);

        expect(scheme.listeners.size).toBe(0);
    });

    it('stops following the browser color scheme', () => {
        const scheme = fakeScheme(false);

        const stop = followTheme('system', fakeRoot(), scheme);
        stop();

        expect(scheme.listeners.size).toBe(0);
    });
});

describe('isTheme', () => {
    it('recognizes every theme', () => {
        expect(['light', 'dark', 'system'].every(isTheme)).toBe(true);
    });

    it('rejects anything else', () => {
        expect([undefined, null, 'blue', 1].some(isTheme)).toBe(false);
    });
});
