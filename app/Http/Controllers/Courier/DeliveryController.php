<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function accept(Delivery $delivery)
    {
        if ($delivery->courier_id !== null) {
            return back()->withErrors(['error' => 'This delivery has already been accepted by another courier.']);
        }

        $delivery->update([
            'courier_id' => auth()->user()->courier->id,
            'status' => 'scheduled'
        ]);

        return back()->with('success', 'Delivery accepted successfully.');
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
        if ($order && $order->distributor && !$delivery->seller_address) {
            $delivery->update([
                'seller_address' => $order->distributor->address ?? $order->distributor->company_name,
            ]);
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
        if (!$order || $order->order_number !== $request->order_number) {
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

        if (!$delivery->item_scanned_at) {
            return back()->withErrors(['error' => 'Please scan the item first before confirming pickup.']);
        }

        $delivery->update([
            'status' => 'in_transit',
            'pickup_confirmed_at' => now(),
        ]);

        $delivery->order->update(['status' => 'shipped']);

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
            'proof_photo'  => 'required|image|max:5120',
        ]);

        $order = $delivery->order;
        if (!$order || $order->order_number !== $request->order_number) {
            return back()->withErrors(['error' => 'Scanned code does not match this delivery. Check the package.']);
        }

        $photoPath = $request->file('proof_photo')->store('delivery-proofs', 'public');

        $delivery->update([
            'item_scanned_at'         => now(),
            'proof_of_delivery_path'  => $photoPath,
            'status'                  => 'delivered',
            'actual_delivery_at'      => now(),
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
            'status' => 'required|in:delivered,failed'
        ]);

        $delivery->update(['status' => $request->status]);

        if ($request->status === 'delivered') {
            $order = $delivery->order;
            $order->update([
                'status'       => 'delivered',
                'delivered_at' => now(),
            ]);
            $delivery->update(['actual_delivery_at' => now()]);

            if ($order->payment_method === 'cod') {
                // COD: record cash collected — payout is HELD until distributor confirms remittance
                $delivery->update([
                    'cod_amount'       => $order->total_amount,
                    'cod_collected_at' => now(),
                    // courier_payout_status stays 'pending' — released only after remittance confirmed
                ]);
            } else {
                // Non-COD: release courier payout immediately
                $this->releaseCourierPayout($delivery);
            }
        }

        return back()->with('success', 'Delivery status updated to ' . str_replace('_', ' ', $request->status));
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

        if (!$delivery->cod_collected_at) {
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
                'courier_paid_at'       => now(),
            ]);
        }
    }
}
