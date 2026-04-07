<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DssAlert;
use App\Models\DssDistributorSettings;
use App\Models\DssReorderRecommendation;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\DashboardAnalyticsService;
use App\Services\DssEngineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DssController extends Controller
{
    public function __construct(
        private DssEngineService $engine,
        private DashboardAnalyticsService $analytics,
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        if (! $distributor) {
            abort(403, 'Distributor profile is required.');
        }

        $canViewFinancials = $user->role !== 'staff';
        $paidStatuses = ['delivered', 'completed'];

        $request->validate([
            'ord_from' => 'nullable|date',
            'ord_to' => 'nullable|date',
            'top_from' => 'nullable|date',
            'top_to' => 'nullable|date',
            'top_metric' => 'nullable|in:revenue,units,orders',
            'trend_scope' => 'nullable|in:mtd,yoy',
        ]);

        $trendScope = $request->input('trend_scope', 'mtd');
        $topMetric = $request->input('top_metric', 'revenue');
        if (! $canViewFinancials && $topMetric === 'revenue') {
            $topMetric = 'units';
        }

        [$ordFrom, $ordTo, $ordLabel] = $this->analytics->parseRange($request, 'ord_from', 'ord_to', 29);
        [$topFrom, $topTo, $topLabel] = $this->analytics->parseRange($request, 'top_from', 'top_to', 29);

        $this->engine->syncForDistributor($distributor->id);
        $insights = $this->engine->getInsights($distributor->id);

        $velocityHeatmap = $this->analytics->buildVelocityHeatmap($distributor->id, $paidStatuses);
        $demandForecast = $this->analytics->buildDemandForecast($distributor->id, $paidStatuses, $canViewFinancials, $trendScope);

        $ordBuckets = $this->analytics->buildDateBuckets($ordFrom, $ordTo);
        $ordersSeries = [];
        foreach ($ordBuckets as $b) {
            $ordersSeries[] = [
                'label' => $b['label'],
                'count' => (int) Order::where('distributor_id', $distributor->id)
                    ->whereBetween('created_at', [$b['start'], $b['end']])
                    ->count(),
            ];
        }

        $topProducts = [];
        if ($canViewFinancials || in_array($topMetric, ['units', 'orders'], true)) {
            $topProducts = $this->analytics->buildTopProducts(
                $distributor->id, $paidStatuses, $topFrom, $topTo, $topMetric, $canViewFinancials
            );
        }

        $unitsLast30 = (int) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.distributor_id', $distributor->id)
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.updated_at', [now()->subDays(30)->startOfDay(), now()->endOfDay()])
            ->sum('order_items.quantity');

        $invAgg = Inventory::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty_total')
            ->first();

        $qtyTotal = (int) ($invAgg->qty_total ?? 0);
        $dailyBurn = $unitsLast30 > 0 ? $unitsLast30 / 30.0 : 0.0;
        $daysCover = $dailyBurn > 0 ? $qtyTotal / $dailyBurn : null;
        $inventoryGauge = $this->analytics->buildInventoryGauge($qtyTotal, $daysCover);

        return Inertia::render('Owner/Insights/Index', [
            'canViewFinancials' => $canViewFinancials,
            'insights' => $insights,
            'filters' => [
                'trend_scope' => $trendScope,
                'new_orders' => ['from' => $ordFrom->toDateString(), 'to' => $ordTo->toDateString(), 'label' => $ordLabel],
                'top_products' => ['from' => $topFrom->toDateString(), 'to' => $topTo->toDateString(), 'label' => $topLabel, 'metric' => $topMetric],
            ],
            'analytics' => [
                'velocity_heatmap' => $velocityHeatmap,
                'demand_forecast' => $demandForecast,
                'inventory_gauge' => $inventoryGauge,
            ],
            'charts' => [
                'orders_series' => $ordersSeries,
                'orders_period_label' => $ordLabel,
                'top_products' => $topProducts,
                'top_metric' => $topMetric,
                'top_period_label' => $topLabel,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $distributor = auth()->user()->distributor;
        if (! $distributor) {
            abort(403);
        }

        $validated = $request->validate([
            'low_stock_threshold_days' => 'required|integer|min:1|max:90',
            'expiry_warning_days' => 'required|integer|min:1|max:365',
            'dead_stock_days' => 'required|integer|min:1|max:365',
            'enable_auto_alerts' => 'required|boolean',
        ]);

        DssDistributorSettings::updateOrCreate(
            ['distributor_id' => $distributor->id],
            $validated
        );

        return back()->with('success', 'Settings updated successfully.');
    }

    public function markAlertRead(DssAlert $alert)
    {
        $distributor = auth()->user()->distributor;
        if (! $distributor || $alert->distributor_id !== $distributor->id) {
            abort(403);
        }

        $alert->markAsRead();

        return back()->with('success', 'Alert marked as read.');
    }

    public function actionRecommendation(DssReorderRecommendation $recommendation)
    {
        $distributor = auth()->user()->distributor;
        if (! $distributor || $recommendation->distributor_id !== $distributor->id) {
            abort(403);
        }

        $recommendation->markAsActioned();

        return back()->with('success', 'Recommendation marked as actioned.');
    }
}
