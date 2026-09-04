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

        // 3. Create Granular Admin Permissions (admin.*)
        // Grouped by functional area for clean UI display
        $adminPermissions = [
            // Distributor application lifecycle
            'admin.applications.review',  // View pending applications
            'admin.applications.approve', // Approve an application
            'admin.applications.reject',  // Reject an application

            // Platform-wide order visibility
            'admin.orders.view',

            // Product catalog moderation
            'admin.products.view',
            'admin.products.hide',        // Deactivate / hide from catalog
            'admin.products.remove',      // Soft-delete from catalog

            // Courier management
            'admin.couriers.create',      // Create a new courier account

            // Review & dispute resolution
            'admin.disputes.review',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 4. Create Core Roles (Platform)
        // Super Admin gets everything (always re-synced to pick up new permissions)
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'distributor_id' => null]);
        $superAdmin->syncPermissions(Permission::all());

        // 5. Create Core Roles (Distributor)
        // Note: Distributor roles are created dynamically by the distributor,
        // but we can create a generic 'Owner' role that is automatically assigned to the shop creator.
        // Wait, since 'Owner' is scoped to each distributor, we won't seed it here globally.
        // We will assign all shop permissions to the distributor owner dynamically when they are created,
        // or we don't assign them Spatie roles at all, and just check $user->role === 'distributor'.
    }
}
