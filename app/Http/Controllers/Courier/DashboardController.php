<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $courier = auth()->user()->courier;
        $user    = auth()->user();

        // Dispatch Pool: Deliveries that are scheduled (packed, awaiting courier) with no courier assigned yet
        $availableDeliveries = Delivery::with(['order.distributor.user', 'order.items.product', 'order.customer'])
            ->whereNull('courier_id')
            ->where('status', 'scheduled')
            ->latest()
            ->get();


        // Active Deliveries: assigned to this courier, not yet completed
        $myDeliveries = Delivery::with(['order.distributor.user', 'order.customer', 'order.items.product'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'failed'])
            ->latest()
            ->get();

        // Full paginated history
        $history = Delivery::with(['order.distributor.user', 'order.customer'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', ['delivered', 'failed'])
            ->latest()
            ->paginate(20);

        // Earnings summary from deliveries
        $totalEarned = Delivery::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->sum('courier_fee');

        $pendingEarnings = Delivery::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->where('courier_payout_status', 'pending')
            ->sum('courier_fee');

        $transferredEarnings = Delivery::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->where('courier_payout_status', 'paid')
            ->sum('courier_fee');

        $totalDeliveries = Delivery::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->count();

        return Inertia::render('Courier/Dashboard', [
            'availableDeliveries' => $availableDeliveries,
            'myDeliveries'        => $myDeliveries,
            'history'             => $history,
            'courier'             => $courier,
            'earnings'            => [
                'total_earned'     => (float) $totalEarned,
                'total_deliveries' => $totalDeliveries,
                'pending_earnings' => (float) $pendingEarnings,
                'transferred_earnings' => (float) $transferredEarnings,
            ],
        ]);
    }
}
