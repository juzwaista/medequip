<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Rules\SafeUpload;
use App\Services\OrderChatAutomationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * @deprecated Use App\Http\Controllers\Courier\DeliveryController instead.
 *             This controller is retained only for reference; its routes have been moved.
 */
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

        if (! $courier) {
            // Auto-create courier profile if missing (simplified for demo)
            $courier = $user->courier()->create([
                'vehicle_type' => 'Motorcycle',
                'status' => 'active',
            ]);
        }

        $deliveries = $courier->deliveries()
            ->with(['order.customer', 'order.distributor'])
            ->latest()
            ->get();

        return Inertia::render('Courier/Dashboard', [
            'courier' => $courier,
            'deliveries' => $deliveries,
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
        $scannedNumber = $request->query('order_number');

        $order = Order::where('order_number', $scannedNumber)
            ->with(['customer', 'distributor'])
            ->first();

        if (! $order) {
            return response()->json(null, 404);
        }

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'delivery_address' => $order->delivery_address,
            'customer' => $order->customer,
            'distributor' => $order->distributor,
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
            'photo' => ['nullable', 'required_if:action,deliver', 'file', 'mimes:jpeg,png,jpg,gif,webp,heic,heif', 'max:15360', SafeUpload::image()],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $courier = $user->courier;

        if (! $courier) {
            $courier = $user->courier()->create([
                'vehicle_type' => 'Motorcycle',
                'status' => 'active',
            ]);
        }

        $order = Order::where('order_number', $validated['order_number'])->first();

        if (! $order) {
            return back()->withErrors(['message' => 'Order not found.']);
        }

        // Auto-create/find delivery record
        $delivery = Delivery::firstOrCreate(
            ['order_id' => $order->id],
            [
                'tracking_number' => 'trk_'.uniqid(),
                'delivery_address' => $order->delivery_address,
                'status' => 'pending',
            ]
        );

        if ($validated['action'] === 'pickup') {
            $delivery->update([
                'courier_id' => $courier->id,
                'status' => 'in_transit',
            ]);
            $order->update(['status' => 'shipped']);
            app(OrderChatAutomationService::class)->sendOrderShippedMessage($order->fresh());

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
                'proof_of_delivery_path' => $path,
            ]);
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);

            if ($delivery->courier_payout_status === 'pending' && (float) $delivery->courier_fee > 0) {
                $courierWallet = $user->wallet;
                if ($courierWallet) {
                    $courierWallet->credit(
                        (float) $delivery->courier_fee,
                        'delivery_fee',
                        (string) $delivery->id,
                        "Courier fee for {$delivery->tracking_number}"
                    );
                }
                $delivery->update([
                    'courier_payout_status' => 'paid',
                    'courier_paid_at' => now(),
                ]);
            }

            return back()->with('success', 'Order delivered successfully!');
        }

        return back();
    }
}
