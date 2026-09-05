<?php
// HTTP request to check what /admin/roles returns in Inertia
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'superadmin@medequip.com')->first();
if (!$user) {
    $user = \App\Models\User::first();
}

if ($user) {
    \Illuminate\Support\Facades\Auth::login($user);
    $controller = app()->make(\App\Http\Controllers\Admin\RoleController::class);
    $response = $controller->index();
    
    // In Inertia, $response is usually an Inertia\Response object
    // We can get the props from it
    if (method_exists($response, 'toResponse')) {
        $request = Illuminate\Http\Request::create('/admin/roles', 'GET');
        $request->headers->set('X-Inertia', 'true');
        
        // We can just dump the props directly instead of doing full HTTP cycle
        $reflection = new ReflectionClass($response);
        $propsProperty = $reflection->getProperty('props');
        $propsProperty->setAccessible(true);
        $props = $propsProperty->getValue($response);
        
        echo "Props:\n";
        echo json_encode($props['permissions'], JSON_PRETTY_PRINT);
    }
} else {
    echo "No user found to authenticate.";
}
