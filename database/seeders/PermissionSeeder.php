<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        $permissions = [
            // Dashboard
            'dashboard.view',
            // Catalog – Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.publish',
            'products.cost_data.view',
            'products.cost_data.edit',
            // Catalog – Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',
            // Catalog – Brands
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',
            // Catalog – Warranty Plans
            'warranty_plans.view',
            'warranty_plans.create',
            'warranty_plans.edit',
            'warranty_plans.delete',
            // Catalog – Attributes
            'attributes.view',
            'attributes.create',
            'attributes.edit',
            // Vendors
            'vendors.view',
            'vendors.create',
            'vendors.approve',
            'vendors.suspend',
            'vendors.blacklist',
            'vendors.documents.verify',
            'vendors.strikes.issue',
            'vendors.payouts.hold',
            'vendors.assigned_only',
            // Orders
            'orders.view',
            'orders.cancel',
            'orders.refund',
            'orders.dispute',
            'orders.flag_fraud',
            // Finance
            'payouts.view',
            'payouts.approve',
            'payouts.export',
            'commissions.view',
            'commissions.create',
            'commissions.edit',
            'ledger.view',
            'analytics.view',
            'transactions.view',
            // Marketing
            'banners.view',
            'banners.create',
            'banners.edit',
            'banners.delete',
            'flash_sales.view',
            'flash_sales.create',
            'flash_sales.edit',
            'flash_sales.review_submissions',
            'coupons.view',
            'coupons.create',
            'coupons.edit',
            'coupons.delete',
            'gift_cards.view',
            'gift_cards.create',
            'gift_cards.edit',
            'ad_campaigns.view',
            'ad_campaigns.create',
            'ad_campaigns.edit',
            'ad_campaigns.delete',
            'ad_campaigns.manage',
            'campaign_offers.view',
            'vendor_change_requests.view',
            'vendor_change_requests.approve',
            'travel_agency_change_requests.view',
            'travel_agency_change_requests.approve',
            'pages.view',
            'pages.manage',
            'app_contexts.view',
            'app_contexts.manage',
            // Customers
            'customers.view',
            'customers.edit',
            'customers.suspend',
            // Notifications
            'notifications.view',
            'notifications.send',
            // Reviews
            'reviews.view',
            'reviews.approve',
            'reviews.reject',
            'reviews.delete',
            'reviews.edit',
            // Disputes
            'disputes.view',
            'disputes.resolve',
            // Returns
            'returns.view',
            'returns.manage',
            // Carrier Claims
            'carrier-claims.view',
            'carrier-claims.resolve',
            // Warranty Claims
            'warranty_claims.view',
            'warranty_claims.manage',
            // Support
            'support.view',
            'support.reply',
            'support.assign',
            // System
            'countries.view',
            'countries.edit',
            'countries.launch',
            'warehouses.view',
            'warehouses.manage',
            'packaging.manage',
            'settings.view',
            'settings.edit',
            'settings.content',
            'portal_content.view',
            'portal_content.edit',
            'faqs.view',
            'faqs.create',
            'faqs.edit',
            'faqs.delete',
            'admins.view',
            'admins.create',
            'admins.edit',
            'admins.delete',
            'admins.impersonate',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'activity-log.view',
            // Marketers
            'marketers.view',
            'marketers.approve',
            'marketers.reject',
            'marketers.suspend',
            'marketers.campaigns.view',
            'marketers.campaigns.approve',
            'marketers.campaigns.reject',
            'marketers.conversions.view',
            'marketers.conversions.approve',
            'marketers.payouts.view',
            'marketers.payouts.generate',
            'marketers.payouts.approve',
            'marketers.payouts.process',
            'marketers.tiers.view',
            'marketers.tiers.edit',
            'marketers.secret_promotions.view',
            'marketers.secret_promotions.create',
            'marketers.secret_promotions.edit',
            'marketers.samples.view',
            'marketers.samples.approve',
            'marketers.samples.dispatch',
            'marketers.products.view',
            'marketers.products.approve',
            'marketers.products.reject',
            // Travel
            'travel.view',
            'travel.approve',
            'travel.reject',
            'travel.suspend',
            'travel.geography.manage',
            // Classifieds
            'classifieds.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $countPermissions = Permission::where('guard_name', $guard)->count();
        $this->command->info('Permissions seeded: ' . $countPermissions . ' permissions (guard: ' . $guard . ').');

        $this->command->info('Permissions seeded: ' . count($permissions) . ' permissions (guard: ' . $guard . ').');
    }
}
