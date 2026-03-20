<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super';
    protected $description = 'Create the Super Admin account';

    public function handle()
    {
        DB::table('users')->where('email', 'superadmin@medequip.com')->delete();

        DB::table('users')->insert([
            'name'              => 'Super Admin',
            'email'             => 'superadmin@medequip.com',
            'password'          => Hash::make('MedEquip@Admin2024!'),
            'role'              => 'admin',
            'is_super_admin'    => 1,
            'email_verified_at' => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->info('✓ Super Admin created: superadmin@medequip.com / MedEquip@Admin2024!');
        return 0;
    }
}
