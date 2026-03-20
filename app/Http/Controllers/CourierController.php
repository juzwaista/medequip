<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Delivery;

class CourierController extends Controller
{
    /**
     * Courier Dashboard
     */
    public function dashboard()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $courier = $user->courier;

        if (!$courier) {
            // Auto-create courier profile if missing (simplified for demo)
            $courier = $user->courier()->create([
                'vehicle_type' => 'Motorcycle',
                'status' => 'active'
            ]);
        }

        $deliveries = $courier->deliveries()
            ->with(['order.customer', 'order.distributor'])
            ->latest()
            ->get();

        return Inertia::render('Courier/Dashboard', [
            'courier' => $courier,
            'deliveries' => $deliveries
        ]);
    }

    /**
     * Scanner interface
     */
    public function scanner()
    {
        return Inertia::render('Courier/Scanner');
    }

    /**
     * AJAX: Look up an order by order_number for scanner verification
     */
    public function lookupOrder(Request $request)
    {
        $order = Order::where('order_number', $request->query('order_number'))
            ->with(['customer:id,name', 'distributor:id,name'])
            ->first();

        if (!$order) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'status'           => $order->status,
            'delivery_address' => $order->delivery_address,
            'customer'         => $order->customer,
            'distributor'      => $order->distributor,
        ]);
    }

    /**
     * Handle scanned barcode to update delivery status
     */
    public function processScan(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string',
            'action' => 'required|in:pickup,deliver',
            'photo' => 'required_if:action,deliver|image|max:10240'
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $courier = $user->courier;

        $order = Order::where('order_number', $validated['order_number'])->first();

        if (!$order) {
            return back()->withErrors(['message' => 'Order not found.']);
        }

        // Auto-create/find delivery record
        $delivery = Delivery::firstOrCreate(
            ['order_id' => $order->id],
            [
                'tracking_number' => 'trk_' . uniqid(),
                'delivery_address' => $order->delivery_address,
                'status' => 'pending'
            ]
        );

        if ($validated['action'] === 'pickup') {
            $delivery->update([
                'courier_id' => $courier->id,
                'status' => 'in_transit'
            ]);
            $order->update(['status' => 'shipped']);
            return back()->with('success', 'Order picked up successfully!');
        }

        if ($validated['action'] === 'deliver') {
            if ($delivery->courier_id !== $courier->id) {
                return back()->withErrors(['message' => 'Not assigned to this delivery.']);
            }

            $path = null;
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('delivery_proofs', 'public');
            }

            $delivery->update([
                'status' => 'delivered',
                'actual_delivery_at' => now(),
                'proof_of_delivery_path' => $path
            ]);
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now()
            ]);
            return back()->with('success', 'Order delivered successfully!');
        }

        return back();
    }
}
