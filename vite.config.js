import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/components/layout.js',
                'resources/js/components/form.js',
                'resources/js/components/rich-editor.js',
                'resources/js/components/file-upload.js',
                'resources/js/components/flatpickr.js',
                'resources/js/components/select2.js',
                'resources/js/components/slug-input.js',
                'resources/js/components/datatable.js',
                'resources/js/components/column-renderers.js',
                'resources/js/admin/dashboard.js',
                'resources/js/admin/products.js',
                'resources/js/admin/orders.js',
                'resources/js/admin/categories.js',
                'resources/js/admin/attributes.js',
                'resources/js/admin/countries.js',
                'resources/js/admin/cities.js',
                'resources/js/admin/currencies.js',
                'resources/js/admin/brands.js',
                'resources/js/admin/vendors.js',
                'resources/js/admin/payouts.js',
            ],
            refresh: [
                'resources/views/**',
                'routes/**',
                'app/View/Components/**',
            ],
            fonts: [
                bunny('Figtree', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        sourcemap: true,
    },
});
