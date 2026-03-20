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

    public function updateStatus(Request $request, Delivery $delivery)
    {
        if ($delivery->courier_id != auth()->user()->courier->id) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:in_transit,delivered,failed'
        ]);

        $delivery->update(['status' => $request->status]);

        // Sync order status
        $orderNewStatus = null;
        if ($request->status === 'in_transit') {
            $orderNewStatus = 'shipped';
        } elseif ($request->status === 'delivered') {
            $orderNewStatus = 'delivered';
            $delivery->update(['actual_delivery_at' => now()]);
        }

        if ($orderNewStatus) {
            $delivery->order->update(['status' => $orderNewStatus]);
            if ($orderNewStatus === 'delivered') {
                $delivery->order->update(['delivered_at' => now()]);
            }
        }

        return back()->with('success', 'Delivery status updated to ' . str_replace('_', ' ', $request->status));
    }
}
