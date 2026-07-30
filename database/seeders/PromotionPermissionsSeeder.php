<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Admin-guard promotion permissions are already declared in PermissionSeeder
 * (promotion_requests.*, promotion_settings.*, celebrity_store.edit,
 * marketers.tiers.*, marketers.secret_promotions.*, admin_can_manage_*).
 * This seeder only re-asserts them (idempotent) and adds the marketer-guard
 * permissions needed now that Marketer uses HasRoles.
 */
class PromotionPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $adminPermissions = [
            'promotion_requests.view',
            'promotion_requests.edit',
            'celebrity_store.view',
            'celebrity_store.edit',
            'promotion_settings.view',
            'promotion_settings.edit',
            'marketers.tiers.view',
            'marketers.tiers.edit',
            'marketers.secret_promotions.view',
            'marketers.secret_promotions.create',
            'marketers.secret_promotions.edit',
            'admin_can_manage_influencer_promotions',
            'admin_can_manage_marketer_quotas',
        ];

        foreach ($adminPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }

        $marketerPermissions = [
            'influencer_promotions.view',
            'influencer_promotions.respond',
            'quotas.view',
            'qr_codes.view',
            'qr_codes.manage',
            'whatsapp_links.manage',
        ];

        foreach ($marketerPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'marketer']);
        }

        $adminCount = Permission::where('guard_name', 'admin')->count();
        $marketerCount = Permission::where('guard_name', 'marketer')->count();
        $this->command->info("Promotion permissions seeded: {$adminCount} admin, {$marketerCount} marketer.");
    }
}
