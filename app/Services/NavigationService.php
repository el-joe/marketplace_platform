<?php

namespace App\Services;

use App\Enums\ClassifiedListingStatus;
use App\Enums\DisputeStatus;
use App\Enums\MarketerStatus;
use App\Enums\SupportTicketStatus;
use App\Enums\TravelPackageStatus;
use App\Enums\VendorCampaignOfferStatus;
use App\Enums\VendorGlobalStatus;
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
                'group' => __('admin.nav.overview'),
                'icon' => 'home',
                'items' => [
                    [
                        'label' => __('admin.nav.dashboard'),
                        'route' => 'admin.dashboard',
                        'icon' => 'home',
                        'permission' => 'dashboard.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.analytics'),
                        'route' => 'admin.analytics.index',
                        'icon' => 'chart-bar',
                        'permission' => 'analytics.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.catalog'),
                'icon' => 'cube',
                'items' => [
                    [
                        'label' => __('admin.nav.products'),
                        'route' => 'admin.products.index',
                        'icon' => 'cube',
                        'permission' => 'products.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.categories'),
                        'route' => 'admin.categories.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'categories.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.brands'),
                        'route' => 'admin.brands.index',
                        'icon' => 'tag',
                        'permission' => 'brands.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.attributes'),
                        'route' => 'admin.attributes.index',
                        'icon' => 'adjustments-horizontal',
                        'permission' => 'attributes.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.sales'),
                'icon' => 'shopping-cart',
                'items' => [
                    [
                        'label' => __('admin.nav.orders'),
                        'route' => 'admin.orders.index',
                        'icon' => 'shopping-cart',
                        'permission' => 'orders.view',
                        'badge' => $this->cachedBadge('pending_orders', fn() => $this->countPendingOrders()),
                    ],
                    [
                        'label' => __('admin.nav.disputes'),
                        'route' => 'admin.disputes.index',
                        'icon' => 'exclamation-triangle',
                        'permission' => 'disputes.view',
                        'badge' => $this->cachedBadge('open_disputes', fn() => $this->countOpenDisputes()),
                    ],
                    [
                        'label' => __('admin.nav.warranty_claims'),
                        'route' => 'admin.warranty-claims.index',
                        'icon' => 'shield-check',
                        'permission' => 'warranty_claims.view',
                        'badge' => $this->cachedBadge('unresolved_warranty_claims', fn() => $this->countUnresolvedWarrantyClaims()),
                    ],
                    [
                        'label' => __('admin.nav.coupons'),
                        'route' => 'admin.coupons.index',
                        'icon' => 'ticket',
                        'permission' => 'coupons.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.flash_sales'),
                        'route' => 'admin.flash-sales.index',
                        'icon' => 'bolt',
                        'permission' => 'flash_sales.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.gift_cards'),
                        'route' => 'admin.gift-cards.index',
                        'icon' => 'gift',
                        'permission' => 'gift_cards.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.support_tickets'),
                        'route' => 'admin.support-tickets.index',
                        'icon' => 'chat-bubble-left-right',
                        'permission' => 'support.view',
                        'badge' => $this->cachedBadge('open_tickets', fn() => $this->countOpenTickets()),
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.marketing'),
                'icon' => 'megaphone',
                'items' => [
                    [
                        'label' => __('admin.nav.banners'),
                        'route' => 'admin.banners.index',
                        'icon' => 'photo',
                        'permission' => 'banners.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.ad_campaigns'),
                        'route' => 'admin.ad-campaigns.index',
                        'icon' => 'megaphone',
                        'permission' => 'ad_campaigns.view',
                        'badge' => null,
                    ],
                    [
                        'label'      => __('admin.nav.vendor_campaigns'),
                        'route'      => 'admin.vendor-campaign-offers.index',
                        'icon'       => 'rectangle-group',
                        'permission' => 'campaign_offers.view',
                        'badge'      => $this->cachedBadge('pending_campaign_offers', fn() => $this->countPendingCampaignOffers()),
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.users'),
                'icon' => 'users',
                'items' => [
                    [
                        'label' => __('admin.nav.customers'),
                        'route' => 'admin.customers.index',
                        'icon' => 'user-group',
                        'permission' => 'customers.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.notifications'),
                        'route' => 'admin.notification-management.index',
                        'icon' => 'bell',
                        'permission' => 'notifications.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.vendors'),
                        'route' => 'admin.vendors.index',
                        'icon' => 'building-storefront',
                        'permission' => 'vendors.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.applications'),
                        'route' => 'admin.vendors.applications',
                        'icon' => 'inbox-arrow-down',
                        'permission' => 'vendors.view',
                        'badge' => $this->cachedBadge('pending_vendors', fn() => $this->countPendingVendors()),
                    ],
                    [
                        'label' => __('admin.nav.admins'),
                        'route' => 'admin.admins.index',
                        'icon' => 'shield-check',
                        'permission' => 'admins.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.roles'),
                        'route' => 'admin.roles.index',
                        'icon' => 'key',
                        'permission' => 'roles.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.marketers'),
                'icon' => 'user-group',
                'items' => [
                    [
                        'label' => __('admin.nav.all_marketers'),
                        'route' => 'admin.marketers.all.index',
                        'icon' => 'user-group',
                        'permission' => 'marketers.view',
                        // 'badge' => $this->cachedBadge('pending_marketers', fn() => $this->countPendingMarketers()),
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.campaigns'),
                        'route' => 'admin.marketers.campaigns.index',
                        'icon' => 'megaphone',
                        'permission' => 'marketers.campaigns.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.conversions'),
                        'route' => 'admin.marketers.conversions.index',
                        'icon' => 'arrow-trending-up',
                        'permission' => 'marketers.conversions.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.payouts'),
                        'route' => 'admin.marketers.payouts.index',
                        'icon' => 'banknotes',
                        'permission' => 'marketers.payouts.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.sample_requests'),
                        'route' => 'admin.marketers.samples.index',
                        'icon' => 'inbox-stack',
                        'permission' => 'marketers.samples.view',
                        'badge' => $this->cachedBadge('pending_marketer_samples', fn() => $this->countPendingMarketerSamples()),
                    ],
                    [
                        'label' => __('admin.nav.secret_promotions'),
                        'route' => 'admin.secret-promotions.index',
                        'icon' => 'lock-closed',
                        'permission' => 'marketers.secret_promotions.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.classifieds'),
                'icon' => 'home-modern',
                'items' => [
                    [
                        'label' => __('admin.nav.categories'),
                        'route' => 'admin.classifieds.categories.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'classifieds.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.contract_templates'),
                        'route' => 'admin.classifieds.contract-templates.index',
                        'icon' => 'document-text',
                        'permission' => 'classifieds.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.listings'),
                        'route' => 'admin.classifieds.listings.index',
                        'icon' => 'list-bullet',
                        'permission' => 'classifieds.view',
                        'badge' => $this->cachedBadge('pending_classifieds', fn() => $this->countPendingClassifieds()),
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.travel'),
                'icon' => 'globe-alt',
                'items' => [
                    [
                        'label' => __('admin.nav.agencies'),
                        'route' => 'admin.travel.agencies.index',
                        'icon' => 'building-office',
                        'permission' => 'travel.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.packages'),
                        'route' => 'admin.travel.packages.index',
                        'icon' => 'briefcase',
                        'permission' => 'travel.view',
                        'badge' => $this->cachedBadge('pending_travel_packages', fn() => $this->countPendingTravelPackages()),
                    ],
                    [
                        'label' => __('admin.nav.bookings'),
                        'route' => 'admin.travel.bookings.index',
                        'icon' => 'ticket',
                        'permission' => 'travel.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.countries'),
                        'route' => 'admin.travel.countries.index',
                        'icon' => 'flag',
                        'permission' => 'travel.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.cities'),
                        'route' => 'admin.travel.cities.index',
                        'icon' => 'map-pin',
                        'permission' => 'travel.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.finance'),
                'icon' => 'banknotes',
                'items' => [
                    [
                        'label' => __('admin.nav.payouts'),
                        'route' => 'admin.payouts.index',
                        'icon' => 'banknotes',
                        'permission' => 'payouts.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.transactions'),
                        'route' => 'admin.transactions.index',
                        'icon' => 'arrow-trending-up',
                        'permission' => 'transactions.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.ledger'),
                        'route' => 'admin.ledger.index',
                        'icon' => 'book-open',
                        'permission' => 'ledger.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.cod_settlements'),
                        'route' => 'admin.delivery.cod-settlements.index',
                        'icon' => 'banknotes',
                        'permission' => 'payouts.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.financial_reports'),
                        'route' => 'admin.reports.financial.index',
                        'icon' => 'chart-bar-square',
                        'permission' => 'analytics.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.content'),
                'icon' => 'document-text',
                'items' => [
                    [
                        'label' => __('admin.nav.pages'),
                        'route' => 'admin.page-builder.index',
                        'icon' => 'document-text',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.reviews'),
                        'route' => 'admin.reviews.index',
                        'icon' => 'star',
                        'permission' => 'reviews.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.blog_categories'),
                        'route' => 'admin.blog.categories.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.blog'),
                        'route' => 'admin.blog.posts.index',
                        'icon' => 'pencil-square',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.adsupport_collections'),
                        'route' => 'admin.adsupport.collections.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.adsupport_articles'),
                        'route' => 'admin.adsupport.articles.index',
                        'icon' => 'information-circle',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.faqs'),
                        'route' => 'admin.faqs.index',
                        'icon' => 'information-circle',
                        'permission' => 'faqs.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.helpcenter_group'),
                'icon' => 'information-circle',
                'items' => [
                    [
                        'label' => __('admin.nav.helpcenter_categories'),
                        'route' => 'admin.helpcenter.categories.index',
                        'icon' => 'rectangle-stack',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.helpcenter_articles'),
                        'route' => 'admin.helpcenter.articles.index',
                        'icon' => 'information-circle',
                        'permission' => 'pages.view',
                        'badge' => null,
                    ],
                ],
            ],
            [
                'group' => __('admin.nav.system'),
                'icon' => 'cog-6-tooth',
                'items' => [
                    [
                        'label' => __('admin.nav.countries'),
                        'route' => 'admin.countries.index',
                        'icon' => 'globe-alt',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.cities'),
                        'route' => 'admin.cities.index',
                        'icon' => 'map-pin',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.currencies'),
                        'route' => 'admin.currencies.index',
                        'icon' => 'currency-dollar',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.shipping_zones'),
                        'route' => 'admin.shipping-zones.index',
                        'icon' => 'truck',
                        'permission' => 'countries.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.shipping_methods'),
                        'route' => 'admin.shipping-methods.index',
                        'icon' => 'cube',
                        'permission' => 'settings.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.document_types'),
                        'route' => 'admin.vendor-document-types.index',
                        'icon' => 'document-check',
                        'permission' => 'settings.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.payment_methods'),
                        'route' => 'admin.payment-methods.index',
                        'icon' => 'credit-card',
                        'permission' => 'settings.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.warehouses'),
                        'route' => 'admin.warehouses.index',
                        'icon' => 'building-office-2',
                        'permission' => 'warehouses.view',
                        'badge' => null,
                    ],
                    [
                        'label' => __('admin.nav.inventory_transfers'),
                        'route' => 'admin.warehouses.transfers.index',
                        'icon' => 'arrows-right-left',
                        'permission' => 'warehouses.view',
                        'badge' => null,
                    ],
                    // [
                    //     'label' => __('admin.nav.settings'),
                    //     'route' => 'admin.settings.index',
                    //     'icon' => 'cog-6-tooth',
                    //     'permission' => 'settings.view',
                    //     'badge' => null,
                    // ],
                    [
                        'label' => __('admin.nav.activity_log'),
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
            return (int) \App\Models\Dispute::query()->where('status', DisputeStatus::Open->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countUnresolvedWarrantyClaims(): int
    {
        if (!class_exists(\App\Models\WarrantyClaim::class)) {
            return 0;
        }
        try {
            return (int) \App\Models\WarrantyClaim::query()
                ->whereNotIn('status', [\App\Models\WarrantyClaim::STATUS_RESOLVED, \App\Models\WarrantyClaim::STATUS_REJECTED])
                ->count();
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
            return (int) \App\Models\Vendor::query()->where('global_status', VendorGlobalStatus::Pending->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countOpenTickets(): int
    {
        try {
            return (int) \App\Models\SupportTicket::query()->where('status', SupportTicketStatus::Open->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingCampaignOffers(): int
    {
        try {
            return (int) \App\Models\VendorCampaignOffer::query()->where('status', VendorCampaignOfferStatus::PendingAdmin->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingMarketers(): int
    {
        try {
            return (int) \App\Models\Marketer::query()->where('status', MarketerStatus::Pending->value)->count();
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

    protected function countPendingClassifieds(): int
    {
        try {
            return (int) \App\Models\ClassifiedListing::query()->where('status', ClassifiedListingStatus::PendingReview->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function countPendingTravelPackages(): int
    {
        try {
            return (int) \App\Models\TravelPackage::query()->where('status', TravelPackageStatus::PendingReview->value)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
