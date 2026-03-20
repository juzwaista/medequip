<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $courier = auth()->user()->courier;

        // Dispatch Pool: Deliveries that are pending and have no courier assigned yet
        $availableDeliveries = Delivery::with(['order.distributor', 'order.items.product', 'order.customer'])
            ->whereNull('courier_id')
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Active Deliveries: Deliveries assigned to this courier that are not yet delivered/failed
        $myDeliveries = Delivery::with(['order.distributor', 'order.customer', 'order.items.product'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'failed'])
            ->latest()
            ->get();

        // History: Past completed or failed deliveries
        $history = Delivery::with(['order.distributor', 'order.customer'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', ['delivered', 'failed'])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Courier/Dashboard', [
            'availableDeliveries' => $availableDeliveries,
            'myDeliveries' => $myDeliveries,
            'history' => $history,
            'courier' => $courier
        ]);
    }
}
