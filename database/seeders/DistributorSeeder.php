<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Distributor;
use App\Models\DssDistributorSettings;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DistributorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $distributors = [
            [
                'company_name' => 'MedTech Solutions Inc.',
                'email' => 'contact@medtechsolutions.com',
                'contact_number' => '09171234567',
                'address' => '123 Medical Drive, Imus, Cavite',
                'description' => 'Leading supplier of diagnostic and monitoring equipment in Cavite',
                'is_verified' => true,
                'auto_approve_orders' => true,
                'branches' => [
                    ['branch_name' => 'Main Branch', 'address' => '123 Medical Drive, Imus, Cavite', 'contact_number' => '09171234567'],
                    ['branch_name' => 'Bacoor Branch', 'address' => '456 Health Street, Bacoor, Cavite', 'contact_number' => '09171234568'],
                ],
            ],
            [
                'company_name' => 'PharmaCare Distributors',
                'email' => 'info@pharmacare.ph',
                'contact_number' => '09181234567',
                'address' => '789 Wellness Ave, Dasmarinas, Cavite',
                'description' => 'Complete medical supplies and PPE provider',
                'is_verified' => true,
                'auto_approve_orders' => false,
                'branches' => [
                    ['branch_name' => 'Main Warehouse', 'address' => '789 Wellness Ave, Dasmarinas, Cavite', 'contact_number' => '09181234567'],
                ],
            ],
            [
                'company_name' => 'Surgical Instruments PH',
                'email' => 'sales@surgicalph.com',
                'contact_number' => '09191234567',
                'address' => '321 Surgery Road, Cavite City, Cavite',
                'description' => 'Specialized in surgical instruments and tools',
                'is_verified' => true,
                'auto_approve_orders' => true,
                'branches' => [
                    ['branch_name' => 'Cavite City Store', 'address' => '321 Surgery Road, Cavite City, Cavite', 'contact_number' => '09191234567'],
                    ['branch_name' => 'Tagaytay Branch', 'address' => '654 Medical Plaza, Tagaytay, Cavite', 'contact_number' => '09191234569'],
                ],
            ],
            [
                'company_name' => 'LabEquip Corp',
                'email' => 'hello@labequip.com.ph',
                'contact_number' => '09201234567',
                'address' => '111 Science Park, Trece Martires, Cavite',
                'description' => 'Laboratory equipment and consumables specialist',
                'is_verified' => true,
                'auto_approve_orders' => false,
                'branches' => [], // No branches - inventory tracked at distributor level
            ],
        ];

        foreach ($distributors as $index => $distributorData) {
            // Create user account
            $user = User::create([
                'name' => $distributorData['company_name'],
                'email' => $distributorData['email'],
                'password' => 'CurrentP4ss!',
                'email_verified_at' => now(),
            ]);
            $user->forceFill(['role' => 'distributor'])->save();

            // Extract branches
            $branches = $distributorData['branches'];
            unset($distributorData['branches']);

            // Create distributor
            $distributorData['user_id'] = $user->id;
            $distributor = Distributor::create($distributorData);
            $distributor->update([
                'status' => 'approved',
                'slug' => Str::slug($distributorData['company_name']).'-'.$distributor->id,
                'shop_profile_onboarding_completed_at' => now(),
            ]);

            // Create branches
            foreach ($branches as $branchData) {
                $branchData['distributor_id'] = $distributor->id;
                Branch::create($branchData);
            }

            // Create DSS settings with default values
            DssDistributorSettings::create([
                'distributor_id' => $distributor->id,
                'low_stock_threshold_days' => 7,
                'expiry_warning_days' => 60,
                'dead_stock_days' => 90,
                'enable_auto_alerts' => true,
            ]);
        }

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@medequip.com',
            'password' => 'CurrentP4ss!',
            'email_verified_at' => now(),
        ]);
        $admin->forceFill(['role' => 'admin'])->save();

        // Create sample customers
        for ($i = 1; $i <= 5; $i++) {
            $customer = User::create([
                'name' => "Customer {$i}",
                'email' => "customer{$i}@example.com",
                'password' => 'CurrentP4ss!',
                'phone_number' => '0917000'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'email_verified_at' => now(),
            ]);
            $customer->forceFill(['role' => 'customer'])->save();
        }
    }
}
