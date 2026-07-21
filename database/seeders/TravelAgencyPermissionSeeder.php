<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TravelAgencyPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'travel_agency';

        $permissions = [
            'packages.view',
            'packages.create',
            'packages.edit',
            'bookings.view',
            'bookings.create',
            'bookings.manage',
            'campaigns.view',
            'campaigns.manage',
            'inquiries.view',
            'inquiries.manage',
            'profile.view',
            'profile.edit',
            'team.view',
            'team.invite',
            'team.manage',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'bank_accounts.view',
            'bank_accounts.manage',
            'reports.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        $managerPermissions = [
            'packages.view', 'packages.create', 'packages.edit',
            'bookings.view', 'bookings.create', 'bookings.manage',
            'campaigns.view', 'campaigns.manage',
            'inquiries.view', 'inquiries.manage',
            'profile.view',
            'reports.view',
        ];

        $staffPermissions = [
            'packages.view',
            'bookings.view', 'bookings.create',
            'campaigns.view',
            'inquiries.view', 'inquiries.manage',
            'profile.view',
        ];

        $roleLabels = [
            'travel_agency_owner' => 'Owner',
            'travel_agency_manager' => 'Manager',
            'travel_agency_staff' => 'Staff',
        ];

        foreach (
            [
                'travel_agency_owner' => $permissions,
                'travel_agency_manager' => $managerPermissions,
                'travel_agency_staff' => $staffPermissions,
            ] as $roleName => $rolePermissions
        ) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->fill(['travel_agency_id' => null, 'label' => $roleLabels[$roleName]])->save();
            $role->syncPermissions($rolePermissions);
        }

        $countPermissions = Permission::where('guard_name', $guard)->count();
        $this->command->info('Travel agency permissions seeded: ' . $countPermissions . ' permissions (guard: ' . $guard . ').');
    }
}
