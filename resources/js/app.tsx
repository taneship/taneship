import { createInertiaApp, router } from '@inertiajs/react';

import { followTheme, isTheme } from '@/lib/theme';

if (!import.meta.env.SSR) {
    let stopFollowingTheme = () => {};

    const followPageTheme = (theme: unknown) => {
        stopFollowingTheme();
        stopFollowingTheme = followTheme(
            isTheme(theme) ? theme : 'system',
            document.documentElement,
            window.matchMedia('(prefers-color-scheme: dark)'),
        );
    };

    // A visit to the current URL replaces the page without a navigate event: success covers it.
    router.on('navigate', ({ detail }) => followPageTheme(detail.page.props.theme));
    router.on('success', ({ detail }) => followPageTheme(detail.page.props.theme));
}

void createInertiaApp({
    pages: './pages',
    // The page given to title() does not carry the shared props' type.
    title: (title, { props: { name } }) =>
        typeof name === 'string' ? `${title} - ${name}` : title,
    strictMode: true,
});
