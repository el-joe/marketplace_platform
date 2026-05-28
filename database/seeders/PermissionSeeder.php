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
            // Catalog – Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.publish',
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
            'ad_campaigns.view',
            'ad_campaigns.manage',
            'pages.view',
            'pages.manage',
            // Customers
            'customers.view',
            'customers.edit',
            'customers.suspend',
            // Reviews
            'reviews.view',
            'reviews.approve',
            'reviews.reject',
            'reviews.delete',
            // Disputes
            'disputes.view',
            'disputes.resolve',
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
            'settings.view',
            'settings.edit',
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
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $this->command->info('Permissions seeded: ' . count($permissions) . ' permissions (guard: ' . $guard . ').');
    }
}
