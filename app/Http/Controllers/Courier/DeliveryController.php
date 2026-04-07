<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Notifications\CourierDeliveryUpdateNotification;
use App\Notifications\OrderNotification;
use App\Rules\SafeUpload;
use App\Services\OrderChatAutomationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeliveryController extends Controller
{
    public function scanner()
    {
        return Inertia::render('Courier/Scanner');
    }

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
            return back()->withErrors(['message' => 'No courier profile found.']);
        }

        $order = Order::where('order_number', $validated['order_number'])->first();

        if (! $order) {
            return back()->withErrors(['message' => 'Order not found.']);
        }

        $delivery = Delivery::firstOrCreate(
            ['order_id' => $order->id],
            [
                'tracking_number' => Delivery::generateTrackingNumber(),
                'delivery_address' => $order->delivery_address,
                'status' => 'scheduled',
            ]
        );

        if ($validated['action'] === 'pickup') {
            $delivery->update([
                'courier_id' => $courier->id,
                'status' => 'in_transit',
            ]);
            $order->update(['status' => 'shipped']);
            app(OrderChatAutomationService::class)->sendOrderShippedMessage($order->fresh());

            $order->loadMissing('customer');
            if ($order->customer) {
                $order->customer->notify(new OrderNotification($order, 'order_shipped'));
            }

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
                $this->releaseCourierPayout($delivery);
            }

            $order->loadMissing('customer');
            if ($order->customer) {
                $order->customer->notify(new OrderNotification($order, 'order_delivered'));
                $order->customer->notify(new OrderNotification($order, 'review_prompt'));
            }

            return back()->with('success', 'Order delivered successfully!');
        }

        return back();
    }

    public function accept(Delivery $delivery)
    {
        if ($delivery->courier_id !== null) {
            return back()->withErrors(['error' => 'This delivery has already been accepted by another courier.']);
        }

        $delivery->update([
            'courier_id' => auth()->user()->courier->id,
            'status' => 'scheduled',
        ]);

        $order = $delivery->order()->with('distributor.user')->first();
        if ($order && $order->distributor && $order->distributor->user) {
            $order->distributor->user->notify(new CourierDeliveryUpdateNotification($delivery, 'accepted'));
        }

        return back()->with('success', 'Delivery accepted successfully.');
    }

    public function cancel(Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        if (! in_array($delivery->status, ['scheduled', 'picking_up'])) {
            return back()->withErrors(['error' => 'Delivery cannot be cancelled at this stage.']);
        }

        $order = $delivery->order()->with('distributor.user')->first();

        // Put back into pool
        $delivery->update([
            'courier_id' => null,
            'status' => 'scheduled',
            'pickup_started_at' => null,
            'item_scanned_at' => null,
        ]);

        if ($order && $order->distributor && $order->distributor->user) {
            $order->distributor->user->notify(new CourierDeliveryUpdateNotification($delivery, 'cancelled'));
        }

        return back()->with('success', 'Delivery job cancelled successfully.');
    }

    /**
     * Step 1: Courier taps "Start Delivery" → heading to pickup location.
     */
    public function startPickup(Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        if ($delivery->status !== 'scheduled') {
            return back()->withErrors(['error' => 'Delivery is not in scheduled state.']);
        }

        $delivery->update([
            'status' => 'picking_up',
            'pickup_started_at' => now(),
        ]);

        // Cache seller address from distributor
        $order = $delivery->order()->with('distributor')->first();
        if ($order && $order->distributor && ! $delivery->seller_address) {
            $delivery->update([
                'seller_address' => $order->distributor->address ?? $order->distributor->company_name,
            ]);
        }

        if ($order && $order->distributor && $order->distributor->user) {
            $order->distributor->user->notify(new CourierDeliveryUpdateNotification($delivery, 'started_pickup'));
        }

        return back()->with('success', 'Heading to pickup location.');
    }

    /**
     * Step 2: Courier scans and verifies the item at the seller location.
     */
    public function confirmScan(Request $request, Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        if ($delivery->status !== 'picking_up') {
            return back()->withErrors(['error' => 'Delivery must be in picking_up state.']);
        }

        $request->validate(['order_number' => 'required|string']);

        $order = $delivery->order;
        if (! $order || $order->order_number !== $request->order_number) {
            return back()->withErrors(['error' => 'Scanned order does not match the assigned delivery. Please check the package.']);
        }

        $delivery->update(['item_scanned_at' => now()]);

        return back()->with('success', 'Item scanned and verified.');
    }

    /**
     * Step 3: Courier confirms pickup → package now in transit.
     */
    public function confirmPickup(Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        if (! $delivery->item_scanned_at) {
            return back()->withErrors(['error' => 'Please scan the item first before confirming pickup.']);
        }

        $delivery->update([
            'status' => 'in_transit',
            'pickup_confirmed_at' => now(),
        ]);

        $delivery->order->update(['status' => 'shipped']);

        app(OrderChatAutomationService::class)->sendOrderShippedMessage($delivery->order->fresh());

        $delivery->order->loadMissing('customer');
        if ($delivery->order->customer) {
            $delivery->order->customer->notify(new OrderNotification($delivery->order, 'order_shipped'));
        }

        return back()->with('success', 'Package picked up! Now in transit.');
    }

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
            'proof_photo' => 'required|image|max:5120',
            'proof_latitude' => 'nullable|numeric|between:-90,90',
            'proof_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $order = $delivery->order;
        if (! $order || $order->order_number !== $request->order_number) {
            return back()->withErrors(['error' => 'Scanned code does not match this delivery. Check the package.']);
        }

        $isFlagged = false;
        if ($order->delivery_latitude && $order->delivery_longitude && $request->proof_latitude && $request->proof_longitude) {
            $earthRadius = 6371; // km
            $lat1 = deg2rad($order->delivery_latitude);
            $lon1 = deg2rad($order->delivery_longitude);
            $lat2 = deg2rad($request->proof_latitude);
            $lon2 = deg2rad($request->proof_longitude);

            $latDelta = $lat2 - $lat1;
            $lonDelta = $lon2 - $lon1;

            $a = sin($latDelta / 2) * sin($latDelta / 2) +
                 cos($lat1) * cos($lat2) *
                 sin($lonDelta / 2) * sin($lonDelta / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distanceKm = $earthRadius * $c;

            // If distance > 1km, flag it
            if ($distanceKm > 1.0) {
                $isFlagged = true;
            }
        }

        $photoPath = $request->file('proof_photo')->store('delivery-proofs', 'public');

        $delivery->update([
            'item_scanned_at' => now(),
            'proof_of_delivery_path' => $photoPath,
            'proof_latitude' => $request->proof_latitude,
            'proof_longitude' => $request->proof_longitude,
            'is_location_flagged' => $isFlagged,
            'status' => 'delivered',
            'actual_delivery_at' => now(),
        ]);

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        if ($order->payment_method === 'cod') {
            $delivery->update([
                'cod_amount' => $order->total_amount,
                'cod_collected_at' => now(),
            ]);
        } else {
            $this->releaseCourierPayout($delivery);
        }

        $order->loadMissing('customer');
        if ($order->customer) {
            $order->customer->notify(new OrderNotification($order, 'order_delivered'));
            $order->customer->notify(new OrderNotification($order, 'review_prompt'));
        }

        return back()->with('success', 'Delivery confirmed with photo proof.');
    }

    /**
     * Courier confirms delivery to customer.
     * For COD: records cash collected, holds payout until remittance confirmed.
     * For non-COD: releases courier payout immediately.
     */
    public function updateStatus(Request $request, Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:delivered,failed',
        ]);

        $delivery->update(['status' => $request->status]);

        if ($request->status === 'delivered') {
            $order = $delivery->order;
            $order->update([
                'status' => 'delivered',
                'delivered_at' => now(),
            ]);
            $delivery->update(['actual_delivery_at' => now()]);

            if ($order->payment_method === 'cod') {
                $delivery->update([
                    'cod_amount' => $order->total_amount,
                    'cod_collected_at' => now(),
                ]);
            } else {
                $this->releaseCourierPayout($delivery);
            }

            $order->loadMissing('customer');
            if ($order->customer) {
                $order->customer->notify(new OrderNotification($order, 'order_delivered'));
                $order->customer->notify(new OrderNotification($order, 'review_prompt'));
            }
        }

        return back()->with('success', 'Delivery status updated to '.str_replace('_', ' ', $request->status));
    }

    /**
     * Courier signals they have physically handed cash to the distributor.
     */
    public function markRemittanceSent(Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        $order = $delivery->order;

        if ($order->payment_method !== 'cod') {
            return back()->withErrors(['error' => 'This is not a COD order.']);
        }

        if (! $delivery->cod_collected_at) {
            return back()->withErrors(['error' => 'Delivery must be confirmed as delivered first.']);
        }

        if ($delivery->cod_remittance_sent_at) {
            return back()->with('info', 'Remittance already marked as sent.');
        }

        $delivery->update(['cod_remittance_sent_at' => now()]);

        return back()->with('success', 'Remittance marked as sent. Waiting for distributor confirmation.');
    }

    /**
     * Internal: credit courier fee to wallet.
     */
    private function releaseCourierPayout(Delivery $delivery): void
    {
        if ($delivery->courier_payout_status === 'pending' && (float) $delivery->courier_fee > 0) {
            $courierUser = auth()->user();
            $wallet = $courierUser->wallet;
            if ($wallet) {
                $wallet->credit(
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
    }
}
