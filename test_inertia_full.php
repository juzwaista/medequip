<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'superadmin@medequip.com')->first();
if (!$user) {
    $user = \App\Models\User::first();
}
\Illuminate\Support\Facades\Auth::login($user);

$request = Illuminate\Http\Request::create('/admin/roles', 'GET');
$request->headers->set('X-Inertia', 'true');
$request->headers->set('X-Inertia-Version', '1');

$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
$content = $response->getContent();
$data = json_decode($content, true);

if (isset($data['props']['permissions'])) {
    echo "Permissions length: " . count($data['props']['permissions']) . "\n";
    echo "Type: " . gettype($data['props']['permissions']) . "\n";
    echo json_encode($data['props']['permissions'], JSON_PRETTY_PRINT);
} else {
    echo "No permissions prop!\n";
    echo substr($content, 0, 500);
}
