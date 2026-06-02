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
                'resources/js/admin/flash-sales.js',
                'resources/js/admin/flash-sale-form.js',
                'resources/js/admin/flash-sale-detail.js',
                'resources/js/admin/page-builder.js',
                'resources/js/admin/coupons.js',
                'resources/js/admin/admins.js',
                'resources/js/admin/customers.js',
                'resources/js/admin/disputes.js',
                'resources/js/admin/banners.js',
                'resources/js/admin/ad-campaigns.js',
                'resources/js/admin/vendor-applications.js',
                'resources/js/admin/reviews.js',
                'resources/js/admin/transactions.js',
                'resources/js/admin/ledger.js',
                'resources/js/admin/settings.js',
                'resources/js/admin/shipping-zones.js',
                'resources/js/admin/warehouses.js',
                'resources/js/admin/warehouse-detail.js',
                'resources/js/admin/support-tickets.js',
                'resources/js/admin/analytics.js',
                'resources/js/admin/activity-log.js',
                'resources/js/admin/payment-methods.js',
                'resources/js/admin/shipping-methods.js',
                // Portal (portal.noon.loc)
                'resources/js/portal/app.js',
                'resources/js/portal/registration.js',
                // Partner panel (partner.noon.loc)
                'resources/js/partner/app.js',
                'resources/js/partner/dashboard.js',
                'resources/js/partner/orders.js',
                'resources/js/partner/listings.js',
                'resources/js/partner/inventory.js',
                'resources/js/partner/payouts.js',
                'resources/js/partner/bank-accounts.js',
                'resources/js/partner/flash-sales.js',
                'resources/js/partner/performance.js',
                'resources/js/partner/profile.js',
                'resources/js/partner/team.js',
                'resources/js/partner/support.js',
                'resources/js/partner/disputes.js',
                // Delivery Agent Panel (delivery.noon.loc)
                'resources/js/delivery/app.js',
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
