<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// 1. Patch DeliveryController — insert confirmDelivery before updateStatus
$controllerFile = __DIR__ . '/app/Http/Controllers/Courier/DeliveryController.php';
$ctrl = file_get_contents($controllerFile);

$insert = <<<'PHP'

    /**
     * Delivery flow: Scan package at customer location + upload proof photo → mark delivered.
     * Requires the scanned order_number to match, and a proof photo upload.
     * COD payout is held until the distributor confirms remittance.
     */
    public function confirmDelivery(Request $request, Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        if ($delivery->status !== 'in_transit') {
            return back()->withErrors(['error' => 'Delivery must be in transit to confirm.']);
        }

        $request->validate([
            'order_number' => 'required|string',
            'proof_photo'  => 'required|image|max:5120',
        ]);

        $order = $delivery->order;
        if (!$order || $order->order_number !== $request->order_number) {
            return back()->withErrors(['error' => 'Scanned code does not match this delivery. Check the package.']);
        }

        $photoPath = $request->file('proof_photo')->store('delivery-proofs', 'public');

        $delivery->update([
            'delivery_scanned_at' => now(),
            'proof_photo'         => $photoPath,
            'status'              => 'delivered',
            'actual_delivery_at'  => now(),
        ]);

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        if ($order->payment_method === 'cod') {
            $delivery->update([
                'cod_amount'       => $order->total_amount,
                'cod_collected_at' => now(),
            ]);
        } else {
            $this->releaseCourierPayout($delivery);
        }

        return back()->with('success', 'Delivery confirmed with photo proof.');
    }

PHP;

// Insert before the updateStatus method
$ctrl = str_replace(
    "    /**\n     * Courier confirms delivery to customer.\n     * For COD:",
    $insert . "\n    /**\n     * Courier confirms delivery to customer.\n     * For COD:",
    $ctrl
);

file_put_contents($controllerFile, $ctrl);
echo "Controller updated.\n";

// 2. Patch routes/web.php — add confirm-delivery route
$routesFile = __DIR__ . '/routes/web.php';
$routes = file_get_contents($routesFile);

if (strpos($routes, 'confirm-delivery') === false) {
    $routes = str_replace(
        "Route::post('/deliveries/{delivery}/remittance-sent', [CourierDeliveryController::class, 'markRemittanceSent'])->name('deliveries.remittanceSent');",
        "Route::post('/deliveries/{delivery}/confirm-delivery', [CourierDeliveryController::class, 'confirmDelivery'])->name('deliveries.confirmDelivery');\n    Route::post('/deliveries/{delivery}/remittance-sent', [CourierDeliveryController::class, 'markRemittanceSent'])->name('deliveries.remittanceSent');",
        $routes
    );
    file_put_contents($routesFile, $routes);
    echo "Route added.\n";
} else {
    echo "Route already exists.\n";
}

// 3. Make sure storage link exists
passthru('php artisan storage:link 2>&1');

echo "\nAll backend changes done.\n";
