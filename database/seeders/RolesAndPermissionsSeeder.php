<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Core Platform Permissions (For MedEquip Internal Staff)
        $platformPermissions = [
            'manage-users',
            'manage-inventory',
            'approve-distributors',
            'view-financials',
            'manage-couriers',
            'view-reports',
            'manage-settings',
        ];

        foreach ($platformPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Core Distributor Permissions (For Shop Staff)
        // These are prefixed with 'shop.' to differentiate them
        $shopPermissions = [
            'shop.manage-orders',
            'shop.manage-inventory',
            'shop.view-reports',
            'shop.manage-staff',
            'shop.manage-settings',
        ];

        foreach ($shopPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 3. Create Core Roles (Platform)
        // Super Admin gets everything
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'distributor_id' => null]);
        $superAdmin->givePermissionTo(Permission::all());

        // 4. Create Core Roles (Distributor)
        // Note: Distributor roles are created dynamically by the distributor,
        // but we can create a generic 'Owner' role that is automatically assigned to the shop creator.
        // Wait, since 'Owner' is scoped to each distributor, we won't seed it here globally.
        // We will assign all shop permissions to the distributor owner dynamically when they are created,
        // or we don't assign them Spatie roles at all, and just check $user->role === 'distributor'.
    }
}
