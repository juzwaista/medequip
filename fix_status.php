<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Reset any bad status values to 'scheduled' so the ENUM ALTER will work
$count = DB::table('deliveries')
    ->whereNotIn('status', ['scheduled', 'in_transit', 'delivered', 'failed'])
    ->update(['status' => 'scheduled']);

echo "Fixed {$count} rows with invalid status.\n";
echo "Current distinct statuses: ";
$statuses = DB::table('deliveries')->distinct()->pluck('status');
echo $statuses->implode(', ') . "\n";
