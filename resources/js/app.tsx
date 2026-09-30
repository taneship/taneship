import { createInertiaApp } from '@inertiajs/react';

void createInertiaApp({
    pages: './pages',
    // The page given to title() does not carry the shared props' type.
    title: (title, { props: { name } }) =>
        typeof name === 'string' ? `${title} - ${name}` : title,
    strictMode: true,
});
