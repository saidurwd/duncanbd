import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/site/app.scss',
                'resources/js/site/app.js',
                'resources/scss/admin/app.scss',
                'resources/js/admin/app.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Fonts and images must be real files: the CSP does not allow data: URIs.
        assetsInlineLimit: 0,
    },
    css: {
        preprocessorOptions: {
            scss: { quietDeps: true, silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'mixed-decls'] },
        },
    },
});
