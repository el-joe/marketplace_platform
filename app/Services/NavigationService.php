<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NavigationService
{
    /**
     * Cache TTL for badge counts in seconds.
     */
    protected const BADGE_CACHE_TTL = 60;

    /**
     * Build the admin sidebar navigation, filtered by permissions.
     *
     * @return array<int, array{group: string, icon: string, items: array<int, array<string, mixed>>}>
     */
    public function adminNavigation(): array
    {
        $groups = [
            [
                'group' => 'Overview',
                'icon' => 'home',
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'admin.dashboard',
                        'icon' => 'home',
                        'permission' => 'dashboard.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Analytics',
                        'route' => 'admin.analytics.index',
                        'icon' => 'chart-bar',
                        'permission' => 'analytics.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Catalog',
                'icon' => 'cube',
                'items' => [
                    [
                        'label' => 'Products',
                        'route' => 'admin.products.index',
                        'icon' => 'cube',
                        'permission' => 'products.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Categories',
                        'route' => 'admin.categories.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'categories.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Brands',
                        'route' => 'admin.brands.index',
                        'icon' => 'tag',
                        'permission' => 'brands.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Attributes',
                        'route' => 'admin.attributes.index',
                        'icon' => 'adjustments-horizontal',
                        'permission' => 'attributes.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Sales',
                'icon' => 'shopping-cart',
                'items' => [
                    [
                        'label' => 'Orders',
                        'route' => 'admin.orders.index',
                        'icon' => 'shopping-cart',
                        'permission' => 'orders.view',
                        'badge' => $this->cachedBadge('pending_orders', fn() => $this->countPendingOrders()),
                    ],
                    [
                        'label' => 'Disputes',
                        'route' => 'admin.disputes.index',
                        'icon' => 'exclamation-triangle',
                        'permission' => 'disputes.view',
                        'badge' => $this->cachedBadge('open_disputes', fn() => $this->countOpenDisputes()),
                    ],
                    [
                        'label' => 'Coupons',
                        'route' => 'admin.coupons.index',
                        'icon' => 'ticket',
                        'permission' => 'coupons.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Flash Sales',
                        'route' => 'admin.flash-sales.index',
                        'icon' => 'bolt',
                        'permission' => 'flash_sales.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Support Tickets',
                        'route' => 'admin.support-tickets.index',
                        'icon' => 'chat-bubble-left-right',
                        'permission' => 'support.view',
                        'badge' => $this->cachedBadge('open_tickets', fn() => $this->countOpenTickets()),
                    ],
                ],
            ],
            [
                'group' => 'Marketing',
                'icon' => 'megaphone',
                'items' => [
                    [
                        'label' => 'Banners',
                        'route' => 'admin.banners.index',
                        'icon' => 'photo',
                        'permission' => 'banners.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Ad Campaigns',
                        'route' => 'admin.ad-campaigns.index',
                        'icon' => 'megaphone',
                        'permission' => 'ad_campaigns.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Users',
                'icon' => 'users',
                'items' => [
                    [
                        'label' => 'Customers',
                        'route' => 'admin.customers.index',
                        'icon' => 'user-group',
                        'permission' => 'customers.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Vendors',
                        'route' => 'admin.vendors.index',
                        'icon' => 'building-storefront',
                        'permission' => 'vendors.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Applications',
                        'route' => 'admin.vendors.applications',
                        'icon' => 'inbox-arrow-down',
                        'permission' => 'vendors.view',
                        'badge' => $this->cachedBadge('pending_vendors', fn() => $this->countPendingVendors()),
                    ],
                    [
                        'label' => 'Admins',
                        'route' => 'admin.admins.index',
                        'icon' => 'shield-check',
                        'permission' => 'admins.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Roles',
                        'route' => 'admin.roles.index',
                        'icon' => 'key',
                        'permission' => 'roles.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Marketers',
                'icon' => 'user-group',
                'items' => [
                    [
                        'label' => 'All Marketers',
                        'route' => 'admin.marketers.all.index',
                        'icon' => 'user-group',
                        'permission' => 'marketers.view',
                        'badge' => $this->cachedBadge('pending_marketers', fn() => $this->countPendingMarketers()),
                    ],
                    [
                        'label' => 'Campaigns',
                        'route' => 'admin.marketers.campaigns.index',
                        'icon' => 'megaphone',
                        'permission' => 'marketers.campaigns.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Conversions',
                        'route' => 'admin.marketers.conversions.index',
                        'icon' => 'arrow-trending-up',
                        'permission' => 'marketers.conversions.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Payouts',
                        'route' => 'admin.marketers.payouts.index',
                        'icon' => 'banknotes',
                        'permission' => 'marketers.payouts.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Sample Requests',
                        'route' => 'admin.marketers.samples.index',
                        'icon' => 'inbox-stack',
                        'permission' => 'marketers.samples.view',
                        'badge' => $this->cachedBadge('pending_marketer_samples', fn() => $this->countPendingMarketerSamples()),
                    ],
                    [
                        'label' => 'Secret Promotions',
                        'route' => 'admin.secret-promotions.index',
                        'icon' => 'lock-closed',
                        'permission' => 'marketers.secret_promotions.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Finance',
                'icon' => 'banknotes',
                'items' => [
                    [
                        'label' => 'Payouts',
                        'route' => 'admin.payouts.index',
                        'icon' => 'banknotes',
                        'permission' => 'payouts.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Transactions',
                        'route' => 'admin.transactions.index',
                        'icon' => 'arrow-trending-up',
                        'permission' => 'transactions.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Ledger',
                        'route' => 'admin.ledger.index',
                        'icon' => 'book-open',
                        'permission' => 'ledger.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'Content',
                'icon' => 'document-text',
                'items' => [
                    [
                        'label' => 'Pages',
                        'route' => 'admin.page-builder.index',
                        'icon' => 'document-text',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Reviews',
                        'route' => 'admin.reviews.index',
                        'icon' => 'star',
                        'permission' => 'reviews.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => 'System',
                'icon' => 'cog-6-tooth',
                'items' => [
                    [
                        'label' => 'Countries',
                        'route' => 'admin.countries.index',
                        'icon' => 'globe-alt',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Cities',
                        'route' => 'admin.cities.index',
                        'icon' => 'map-pin',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Currencies',
                        'route' => 'admin.currencies.index',
                        'icon' => 'currency-dollar',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Shipping Zones',
                        'route' => 'admin.shipping-zones.index',
                        'icon' => 'truck',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Shipping Methods',
                        'route' => 'admin.shipping-methods.index',
                        'icon' => 'cube',
                        'permission' => 'settings.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Payment Methods',
                        'route' => 'admin.payment-methods.index',
                        'icon' => 'credit-card',
                        'permission' => 'settings.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Warehouses',
                        'route' => 'admin.warehouses.index',
                        'icon' => 'building-office-2',
                        'permission' => 'warehouses.view',
                        'badge' => null,
                    ],
                    [
                        'label' => 'Inventory Transfers',
                        'route' => 'admin.warehouses.transfers.index',
                        'icon' => 'arrows-right-left',
                        'permission' => 'warehouses.view',
                        'badge' => null,
                    ],
                    // [
                    //     'label' => 'Settings',
                    //     'route' => 'admin.settings.index',
                    //     'icon' => 'cog-6-tooth',
                    //     'permission' => 'settings.view',
                    //     'badge' => null,
                    // ],
                    [
                        'label' => 'Activity Log',
                        'route' => 'admin.activity-log.index',
                        'icon' => 'clipboard-document-list',
                        'permission' => 'activity-log.view',
                        'badge' => null,
                    ],
                ],
            ],
        ];

        return $this->filterByPermissions($groups);
    }

    /**
     * Filter navigation groups + items by user permissions.
     */
    protected function filterByPermissions(array $groups): array
    {
        $user = Auth::guard('admin')->user();

        $filtered = [];
        foreach ($groups as $group) {
            $items = array_values(array_filter($group['items'], function ($item) use ($user) {
                if (empty($item['permission'])) {
                    return true;
                }
                if (!$user) {
                    return false;
                }
                return method_exists($user, 'can') ? $user->can($item['permission']) : false;
            }));

            if (!empty($items)) {
                $group['items'] = $items;
                $filtered[] = $group;
            }
        }

        return $filtered;
    }

    /**
     * Detect if the current route matches a route pattern.
     */
    public static function isActive(string $routeName): bool
    {
        // Treat "admin.products.index" as a leaf and "admin.products.*" as a section.
        if (str_ends_with($routeName, '.*')) {
            return request()->routeIs($routeName);
        }
        return request()->routeIs($routeName) || request()->routeIs(rtrim($routeName, '.index') . '.*');
    }

    /**
     * Detect if any item in a group is active.
     */
    public static function isGroupActive(array $group): bool
    {
        foreach ($group['items'] as $item) {
            if (self::isActive($item['route'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cache a badge count value for 60 seconds.
     */
    protected function cachedBadge(string $key, \Closure $resolver): ?int
    {
        $count = Cache::remember("nav.badge.{$key}", self::BADGE_CACHE_TTL, $resolver);
        return $count > 0 ? (int) $count : null;
    }

    protected function countPendingOrders(): int
    {
        if (!class_exists(\App\Models\Order::class)) {
            return 0;
        }
        try {
            return (int) \App\Models\Order::query()->where('status', 'pending')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countOpenDisputes(): int
    {
        if (!class_exists(\App\Models\Dispute::class)) {
            return 0;
        }
        try {
            return (int) \App\Models\Dispute::query()->where('status', 'open')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingVendors(): int
    {
        if (!class_exists(\App\Models\Vendor::class)) {
            return 0;
        }
        try {
            return (int) \App\Models\Vendor::query()->where('global_status', 'pending')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countOpenTickets(): int
    {
        try {
            return (int) \App\Models\SupportTicket::query()->where('status', 'open')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingMarketers(): int
    {
        try {
            return (int) \App\Models\Marketer::query()->where('status', 'pending')->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingMarketerSamples(): int
    {
        try {
            return (int) \App\Models\MarketerSampleRequest::query()->where('status', 'requested')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
