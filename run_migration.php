<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('distributors', 'application_cooldown_until')) {
    Schema::table('distributors', function (Blueprint $table) {
        $table->timestamp('application_cooldown_until')->nullable()->after('suspended_until');
    });
    echo "Column added successfully.\n";
} else {
    echo "Column already exists.\n";
}
