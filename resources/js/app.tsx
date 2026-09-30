import { createInertiaApp } from '@inertiajs/react';

void createInertiaApp({
  pages: './pages',
  title: (title, page) => `${title} - ${page.props.name}`,
  strictMode: true,
});
