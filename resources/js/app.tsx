import { DirectionProvider } from '@base-ui/react/direction-provider';
import { createInertiaApp, router } from '@inertiajs/react';
import { toast } from 'sonner';

import { Toaster } from '@/components/ui/sonner';
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

    router.on('flash', ({ detail: { flash } }) => {
        if (flash.toast) {
            toast[flash.toast.type](flash.toast.message);
        }
    });
}

void createInertiaApp({
    pages: './pages',
    // The page given to title() does not carry the shared props' type.
    title: (title, { props: { name } }) =>
        typeof name === 'string' ? `${title} - ${name}` : title,
    strictMode: true,
    // Called once, with the first page: the direction, like <html dir>, holds until the next full load.
    withApp: (app, { page }) => (
        <DirectionProvider direction={page.props.direction}>
            {app}
            <Toaster dir={page.props.direction} />
        </DirectionProvider>
    ),
});
