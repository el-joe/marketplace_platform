<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles/permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        // // ── Define all permissions (slug => display name) ─────────────────────
        // $permissions = [
        //     // Dashboard & Analytics
        //     'dashboard.view' => 'View Dashboard',
        //     'analytics.view' => 'View Analytics',

        //     // Products (Catalog)
        //     'products.view' => 'View Products',
        //     'products.create' => 'Create Products',
        //     'products.edit' => 'Edit Products',
        //     'products.delete' => 'Delete Products',
        //     'products.review' => 'Review / Approve Products',

        //     // Categories
        //     'categories.view' => 'View Categories',
        //     'categories.create' => 'Create Categories',
        //     'categories.edit' => 'Edit Categories',
        //     'categories.delete' => 'Delete Categories',

        //     // Brands
        //     'brands.view' => 'View Brands',
        //     'brands.create' => 'Create Brands',
        //     'brands.edit' => 'Edit Brands',
        //     'brands.delete' => 'Delete Brands',

        //     // Attributes
        //     'attributes.view' => 'View Attributes',
        //     'attributes.create' => 'Create Attributes',
        //     'attributes.edit' => 'Edit Attributes',
        //     'attributes.delete' => 'Delete Attributes',

        //     // Orders
        //     'orders.view' => 'View Orders',
        //     'orders.manage' => 'Manage Orders (status, cancel, refund)',
        //     'orders.dispute' => 'Handle Order Disputes',
        //     'orders.flag_fraud' => 'Flag Orders as Fraud',

        //     // Disputes
        //     'disputes.view' => 'View Disputes',
        //     'disputes.manage' => 'Manage Disputes',

        //     // Coupons
        //     'coupons.view' => 'View Coupons',
        //     'coupons.manage' => 'Manage Coupons',

        //     // Flash Sales
        //     'flash_sales.view' => 'View Flash Sales',
        //     'flash_sales.manage' => 'Manage Flash Sales',

        //     // Marketing
        //     'banners.view' => 'View Banners',
        //     'banners.manage' => 'Manage Banners',
        //     'ad_campaigns.view' => 'View Ad Campaigns',
        //     'ad_campaigns.manage' => 'Manage Ad Campaigns',

        //     // Customers
        //     'customers.view' => 'View Customers',
        //     'customers.manage' => 'Manage Customers',

        //     // Vendors
        //     'vendors.view' => 'View Vendors',
        //     'vendors.manage' => 'Manage Vendors',
        //     'vendors.approve' => 'Approve / Reject Vendors',

        //     // Payouts & Finance
        //     'payouts.view' => 'View Payouts',
        //     'payouts.approve' => 'Approve & Process Payouts',
        //     'transactions.view' => 'View Transactions',
        //     'ledger.view' => 'View Ledger',

        //     // Content / Page Builder
        //     'pages.view' => 'View Pages (Page Builder)',
        //     'pages.manage' => 'Create & Edit Pages',
        //     'reviews.view' => 'View Reviews',
        //     'reviews.manage' => 'Manage Reviews',

        //     // Support
        //     'support.view' => 'View Support Tickets',
        //     'support.reply' => 'Reply to Support Tickets',
        //     'support.assign' => 'Assign Support Tickets',

        //     // System / Geography
        //     'countries.view' => 'View Countries, Cities & Currencies',
        //     'countries.manage' => 'Manage Countries / Launch',
        //     'warehouses.view' => 'View Warehouses',
        //     'warehouses.manage' => 'Manage Warehouses',
        //     'settings.view' => 'View Settings',
        //     'settings.manage' => 'Manage Settings',

        //     // Admin Management
        //     'admins.view' => 'View Admin Accounts',
        //     'admins.manage' => 'Create & Edit Admin Accounts',
        //     'roles.view' => 'View Roles & Permissions',
        //     'roles.manage' => 'Create & Edit Roles',

        //     // Activity
        //     'activity-log.view' => 'View Activity Log',
        // ];

        // dd(count($permissions) . ' permissions to seed. Check the seeder code to review the list.');
        // // Create every permission under the admin guard
        // foreach ($permissions as $name => $displayName) {
        //     Permission::firstOrCreate(
        //         ['name' => $name, 'guard_name' => $guard],
        //         // Spatie doesn't store display_name by default; name is sufficient
        //     );
        // }

        // ── Create "Super Admin" role ─────────────────────────────────────────
        /** @var Role $superAdmin */
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => $guard],
        );

        // Assign all permissions to Super Admin
        $superAdmin->syncPermissions(
            Permission::where('guard_name', $guard)->pluck('name')->toArray()
        );

        // ── Create "Manager" role with common read/manage permissions ─────────
        $manager = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => $guard],
        );
        $manager->syncPermissions([
            'products.view',
            'products.edit',
            'categories.view',
            'brands.view',
            'attributes.view',
            'orders.view',
            'orders.cancel',
            'orders.refund',
            'disputes.view',
            'disputes.resolve',
            'coupons.view',
            'flash_sales.view',
            'support.view',
            'support.reply',
            'vendors.view',
            'payouts.view',
            'reviews.view',
            'reviews.approve',
            'countries.view',
            'ad_campaigns.view',
            'activity-log.view',
        ]);

        // ── Give super_admin role every permission ────────────────────────────
        $superAdmin = \Spatie\Permission\Models\Role::where('name', 'super_admin')
            ->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions(
                \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->pluck('name')
            );
        }

        // ── Assign "Super Admin" role to admin@admin.com ──────────────────────
        $admin = \App\Models\Admin::where('email', 'admin@admin.com')->first();
        if ($admin) {
            $admin->syncRoles(['super_admin']);
            $admin->syncPermissions(
                \Spatie\Permission\Models\Permission::where('guard_name', 'admin')->pluck('name')
            );
        }

        $this->command->info('Permissions, roles, and Super Admin assignment seeded successfully.');
    }
}
