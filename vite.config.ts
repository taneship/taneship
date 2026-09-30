import inertia from '@inertiajs/vite';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            ssr: 'resources/js/app.tsx',
            refresh: true,
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
    ]),
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    test: {
        include: ['resources/js/**/*.test.ts'],
    },
    lint: {
        plugins: ['typescript', 'react', 'jsx-a11y', 'import'],
        rules: {
            'typescript/no-explicit-any': 'error',
            'typescript/no-non-null-assertion': 'error',
            'typescript/ban-ts-comment': ['error', { 'ts-expect-error': true }],
            'react/rules-of-hooks': 'error',
            'react/exhaustive-deps': 'error',
            'react/only-export-components': 'error',
        },
        overrides: [
            {
                files: ['resources/js/components/ui/**'],
                rules: {
                    'react/only-export-components': 'off',
                },
            },
        ],
        ignorePatterns: ['bootstrap/ssr/**', 'public/**', 'vendor/**'],
        options: {
            denyWarnings: true,
            typeAware: true,
            typeCheck: true,
        },
    },
    fmt: {
        singleQuote: true,
        sortImports: {
            internalPattern: ['@/'],
        },
        sortTailwindcss: {
            functions: ['cn', 'cva'],
            stylesheet: './resources/css/app.css',
        },
        ignorePatterns: ['bootstrap/ssr/**', 'composer.json', 'public/**', 'vendor/**'],
    },
});
