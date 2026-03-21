<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Sales records page for distributors.
     * Shows delivered orders with NET revenue analytics (after platform fee).
     */
    public function index(Request $request)
    {
        $distributor = $this->getDistributor();

        // Base query: all orders for this distributor
        $query = Order::where('distributor_id', $distributor->id)
            ->with([
                'customer:id,name,email',
                'items.product:id,name,brand',
                'invoice:id,order_id,invoice_number,total_amount,status',
                'invoice.payments:id,invoice_id,amount,platform_fee_amount,net_seller_amount,escrow_status,status',
            ])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('order_number', 'like', "%{$request->search}%")
                          ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$request->search}%"));
                });
            })
            ->latest();

        $orders = $query->paginate(25)->withQueryString();

        // Revenue analytics — use NET seller amounts from payments
        $paymentBase = Payment::whereHas('invoice.order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->where('status', 'verified')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to));

        $totalGross       = (clone $paymentBase)->sum('amount');
        $totalFees        = (clone $paymentBase)->sum('platform_fee_amount');
        $totalNetRevenue  = (clone $paymentBase)->sum('net_seller_amount');
        $totalOrders      = Order::where('distributor_id', $distributor->id)
            ->whereIn('status', ['delivered', 'completed'])
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->count();
        $avgOrderValue    = $totalOrders > 0 ? round($totalNetRevenue / $totalOrders, 2) : 0;

        // Month-over-month comparison (NET revenue)
        $thisMonthNet = Payment::whereHas('invoice.order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->where('status', 'verified')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('net_seller_amount');

        $lastMonthNet = Payment::whereHas('invoice.order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->where('status', 'verified')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('net_seller_amount');

        $revenueGrowth = $lastMonthNet > 0
            ? round((($thisMonthNet - $lastMonthNet) / $lastMonthNet) * 100, 1)
            : null;

        // Escrow breakdown
        $escrowHeld     = Payment::whereHas('invoice.order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->where('status', 'verified')->where('escrow_status', 'held')->sum('net_seller_amount');
        $escrowReleased = Payment::whereHas('invoice.order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->where('escrow_status', 'released')->sum('net_seller_amount');

        // Payment status breakdown
        $paymentBreakdown = Invoice::whereHas('order', fn($q) => $q->where('distributor_id', $distributor->id))
            ->select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return Inertia::render('Owner/Sales/Index', [
            'orders'           => $orders,
            'filters'          => $request->only(['status', 'date_from', 'date_to', 'search']),
            'stats'            => [
                'total_gross'     => $totalGross,
                'total_fees'      => $totalFees,
                'total_revenue'   => $totalNetRevenue,
                'total_orders'    => $totalOrders,
                'avg_order_value' => $avgOrderValue,
                'this_month'      => $thisMonthNet,
                'last_month'      => $lastMonthNet,
                'revenue_growth'  => $revenueGrowth,
                'escrow_held'     => $escrowHeld,
                'escrow_released' => $escrowReleased,
            ],
            'payment_breakdown' => $paymentBreakdown,
        ]);
    }
}

