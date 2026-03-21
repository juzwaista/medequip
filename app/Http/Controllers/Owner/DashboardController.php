<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Inventory;
use App\Services\DssAlertService;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        if (!$distributor) {
            if ($user->role === 'staff') {
                abort(403, 'Your staff account is not assigned to a distributor. Please contact your employer.');
            }
            return redirect()->route('owner.distributors.create')
                ->with('info', 'Please create a distributor profile to access this dashboard.');
        }

        // Get DSS Alerts
        $dssService = new DssAlertService();
        $alerts = $dssService->getAlertsForDistributor($distributor->id);

        // Orders Statistics
        $totalOrders = Order::where('distributor_id', $distributor->id)->count();
        $pendingOrders = Order::where('distributor_id', $distributor->id)
            ->where('status', 'pending')
            ->count();
        $processingOrders = Order::where('distributor_id', $distributor->id)
            ->whereIn('status', ['approved', 'packed', 'shipped'])
            ->count();

        // Revenue Statistics
        $totalRevenue = Order::where('distributor_id', $distributor->id)
            ->where('status', 'delivered')
            ->sum('total_amount');

        $monthlyRevenue = Order::where('distributor_id', $distributor->id)
            ->where('status', 'delivered')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('total_amount');

        // Inventory Statistics
        $totalProducts = Product::where('distributor_id', $distributor->id)->count();
        $lowStockCount = Inventory::whereHas('product', function ($q) use ($distributor) {
            $q->where('distributor_id', $distributor->id);
        })
            ->whereRaw('quantity <= reorder_level')
            ->count();

        // Recent Orders
        $recentOrders = Order::with(['customer', 'items'])
            ->where('distributor_id', $distributor->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Owner/Dashboard', [
            'distributor' => $distributor,
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'processingOrders' => $processingOrders,
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'totalProducts' => $totalProducts,
                'lowStockCount' => $lowStockCount,
            ],
            'recentOrders' => $recentOrders,
            'dss_alerts' => $alerts,
            'unreadAlerts' => [], // Placeholder for future notification system
        ]);
    }
}
