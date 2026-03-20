<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\User::whereIn('role', ['admin', 'super_admin'])->first();
if ($admin) {
    $admin->password = Illuminate\Support\Facades\Hash::make('password');
    $admin->save();
    echo "SUCCESS: " . $admin->email . " (Role: " . $admin->role . ")";
} else {
    echo "NO_ADMINS_FOUND";
}
