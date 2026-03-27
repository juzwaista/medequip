<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DssAlert;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\DssAlertService;
use App\Services\DssEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        if (! $distributor) {
            if ($user->role === 'staff') {
                abort(403, 'Your staff account is not assigned to a distributor. Please contact your employer.');
            }

            return redirect()->route('owner.distributors.create')
                ->with('info', 'Please create a distributor profile to access this dashboard.');
        }

        $request->validate([
            'rev_from' => 'nullable|date',
            'rev_to' => 'nullable|date',
            'ord_from' => 'nullable|date',
            'ord_to' => 'nullable|date',
            'top_from' => 'nullable|date',
            'top_to' => 'nullable|date',
            'sum_from' => 'nullable|date',
            'sum_to' => 'nullable|date',
            'top_metric' => 'nullable|in:revenue,units,orders',
            'trend_scope' => 'nullable|in:mtd,yoy',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $canViewFinancials = $user->role !== 'staff';
        $paidStatuses = ['delivered', 'completed'];

        $legacy = $request->filled('date_from') || $request->filled('date_to');
        $granular = collect(['rev_from', 'rev_to', 'ord_from', 'ord_to', 'top_from', 'top_to', 'sum_from', 'sum_to'])
            ->contains(fn (string $k) => $request->filled($k));

        if ($legacy && ! $granular) {
            $dateTo = $request->filled('date_to')
                ? Carbon::parse($request->input('date_to'))->endOfDay()
                : now()->endOfDay();
            $dateFrom = $request->filled('date_from')
                ? Carbon::parse($request->input('date_from'))->startOfDay()
                : $dateTo->copy()->subDays(29)->startOfDay();
            if ($dateFrom->gt($dateTo)) {
                [$dateFrom, $dateTo] = [$dateTo->copy()->subDays(29)->startOfDay(), $dateFrom->copy()->endOfDay()];
            }
            [$revFrom, $revTo, $revLabel] = [$dateFrom->copy(), $dateTo->copy(), $this->formatRangeLabel($dateFrom, $dateTo)];
            [$ordFrom, $ordTo, $ordLabel] = [$dateFrom->copy(), $dateTo->copy(), $revLabel];
            [$topFrom, $topTo, $topLabel] = [$dateFrom->copy(), $dateTo->copy(), $revLabel];
            [$sumFrom, $sumTo, $sumLabel] = [$dateFrom->copy(), $dateTo->copy(), $revLabel];
        } else {
            [$revFrom, $revTo, $revLabel] = $this->parseRange($request, 'rev_from', 'rev_to', 29);
            [$ordFrom, $ordTo, $ordLabel] = $this->parseRange($request, 'ord_from', 'ord_to', 29);
            [$topFrom, $topTo, $topLabel] = $this->parseRange($request, 'top_from', 'top_to', 29);
            [$sumFrom, $sumTo, $sumLabel] = $this->parseRange($request, 'sum_from', 'sum_to', 29);
        }

        $topMetric = $request->input('top_metric', 'revenue');
        if (! $canViewFinancials && $topMetric === 'revenue') {
            $topMetric = 'units';
        }

        $trendScope = $request->input('trend_scope', 'mtd');
        if (! in_array($trendScope, ['mtd', 'yoy'], true)) {
            $trendScope = 'mtd';
        }

        // --- DSS Engine Logic ---
        $dssService = new DssAlertService;
        $alerts = $dssService->getAlertsForDistributor($distributor->id);
        
        $engine = new DssEngineService;
        $engine->syncForDistributor($distributor->id);
        $dssInsights = $engine->getInsights($distributor->id);

        $expiryAlerts = $alerts['expiry_alerts']->values()->all();
        $lowStockAlerts = $alerts['low_stock_alerts']->values()->all();
        $lowStockProductIds = collect($lowStockAlerts)->pluck('product_id')->filter()->unique()->all();

        // --- Core Operations (Live Data - Not Cached) ---
        $totalOrders = Order::where('distributor_id', $distributor->id)->count();
        $pendingOrders = Order::where('distributor_id', $distributor->id)
            ->where('status', 'pending')
            ->count();
        $processingOrders = Order::where('distributor_id', $distributor->id)
            ->whereIn('status', ['approved', 'packed', 'shipped'])
            ->count();

        $ordersPlacedInRange = (int) Order::where('distributor_id', $distributor->id)
            ->whereBetween('created_at', [$sumFrom, $sumTo])
            ->count();

        $revenueInRange = $canViewFinancials
            ? (float) Order::where('distributor_id', $distributor->id)
                ->whereIn('status', $paidStatuses)
                ->whereBetween('updated_at', [$sumFrom, $sumTo])
                ->sum('total_amount')
            : 0.0;

        $totalRevenue = $canViewFinancials
            ? (float) Order::where('distributor_id', $distributor->id)
                ->whereIn('status', $paidStatuses)
                ->sum('total_amount')
            : 0.0;

        $monthlyRevenue = $canViewFinancials
            ? (float) Order::where('distributor_id', $distributor->id)
                ->whereIn('status', $paidStatuses)
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('total_amount')
            : 0.0;

        $revenueMtd = $canViewFinancials
            ? (float) Order::where('distributor_id', $distributor->id)
                ->whereIn('status', $paidStatuses)
                ->whereBetween('updated_at', [now()->copy()->startOfMonth(), now()->endOfDay()])
                ->sum('total_amount')
            : 0.0;

        $ordersMtd = (int) Order::where('distributor_id', $distributor->id)
            ->whereBetween('created_at', [now()->copy()->startOfMonth(), now()->endOfDay()])
            ->count();

        $totalProducts = Product::where('distributor_id', $distributor->id)->count();
        $lowStockCount = Inventory::whereHas('product', function ($q) use ($distributor) {
            $q->where('distributor_id', $distributor->id);
        })
            ->whereRaw('quantity <= reorder_level')
            ->count();

        // --- Smart Order Queue ---
        $recentOrders = Order::with(['customer', 'items.product.category'])
        ->where('distributor_id', $distributor->id)
        ->whereIn('status', ['pending', 'approved', 'packed']) // Only show actionable open orders
        ->orderBy('created_at', 'asc') // Grab oldest open orders first from DB
        ->limit(20)
        ->get()
        ->map(fn (Order $order) => $this->formatOrderForDashboard($order, $lowStockProductIds))
        ->sort(function (array $a, array $b) {
            // 1. Sort by Priority Score first (10 is higher than 1)
            if ($a['priority_score'] !== $b['priority_score']) {
                return $b['priority_score'] <=> $a['priority_score'];
            }
            // 2. If scores match, sort by OLDEST timestamp so longest-waiting is on top
            return strcmp($a['created_at'], $b['created_at']); 
        })
        ->values()
        ->all();

        // --- Heavy Analytical Queries (Cached for 30 minutes) ---
        
        // 1. Revenue Series Chart Cache
        $revCacheKey = "distributor_{$distributor->id}_rev_{$revFrom->format('Ymd')}_{$revTo->format('Ymd')}_" . ($canViewFinancials ? 'fin' : 'ops');
        $revenueSeries = Cache::remember($revCacheKey, now()->addMinutes(30), function () use ($distributor, $paidStatuses, $revFrom, $revTo, $canViewFinancials) {
            $revBuckets = $this->buildDateBuckets($revFrom, $revTo);
            $series = [];
            foreach ($revBuckets as $b) {
                $series[] = [
                    'label' => $b['label'],
                    'revenue' => $canViewFinancials
                        ? (float) Order::where('distributor_id', $distributor->id)
                            ->whereIn('status', $paidStatuses)
                            ->whereBetween('updated_at', [$b['start'], $b['end']])
                            ->sum('total_amount')
                        : 0.0,
                ];
            }
            return $series;
        });

        // 2. Orders Series Chart Cache
        $ordCacheKey = "distributor_{$distributor->id}_ord_{$ordFrom->format('Ymd')}_{$ordTo->format('Ymd')}";
        $ordersSeries = Cache::remember($ordCacheKey, now()->addMinutes(30), function () use ($distributor, $ordFrom, $ordTo) {
            $ordBuckets = $this->buildDateBuckets($ordFrom, $ordTo);
            $series = [];
            foreach ($ordBuckets as $b) {
                $series[] = [
                    'label' => $b['label'],
                    'count' => (int) Order::where('distributor_id', $distributor->id)
                        ->whereBetween('created_at', [$b['start'], $b['end']])
                        ->count(),
                ];
            }
            return $series;
        });

        // 3. Top Products Cache
        $topProducts = [];
        if ($canViewFinancials || in_array($topMetric, ['units', 'orders'], true)) {
            $topCacheKey = "distributor_{$distributor->id}_top_{$topFrom->format('Ymd')}_{$topTo->format('Ymd')}_{$topMetric}_" . ($canViewFinancials ? 'fin' : 'ops');
            $topProducts = Cache::remember($topCacheKey, now()->addMinutes(30), function () use ($distributor, $paidStatuses, $topFrom, $topTo, $topMetric, $canViewFinancials) {
                return $this->buildTopProducts($distributor->id, $paidStatuses, $topFrom, $topTo, $topMetric, $canViewFinancials);
            });
        }

        // 4. Velocity Heatmap Cache (6 weeks rolling)
        $heatmapCacheKey = "distributor_{$distributor->id}_heatmap_6w";
        $velocityHeatmap = Cache::remember($heatmapCacheKey, now()->addMinutes(30), function () use ($distributor, $paidStatuses) {
            return $this->buildVelocityHeatmap($distributor->id, $paidStatuses);
        });

        // 5. Demand Forecast Cache
        $forecastCacheKey = "distributor_{$distributor->id}_forecast_{$trendScope}_" . ($canViewFinancials ? 'fin' : 'ops');
        $demandForecast = Cache::remember($forecastCacheKey, now()->addMinutes(30), function () use ($distributor, $paidStatuses, $canViewFinancials, $trendScope) {
            return $this->buildDemandForecast($distributor->id, $paidStatuses, $canViewFinancials, $trendScope);
        });

        // --- End of Cached Queries ---

        $orderPipeline = $this->buildOrderPipeline($distributor->id);

        $invAgg = Inventory::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty_total, COALESCE(SUM(reserved_quantity), 0) as res_total')
            ->first();

        $recommendations = collect($dssInsights['recommendations'])->map(function ($r) {
            return [
                'id' => $r->id,
                'product_id' => $r->product_id,
                'product_name' => optional($r->product)->name ?? ('Product #'.$r->product_id),
                'priority' => $r->priority,
                'current_stock' => (int) $r->current_stock,
                'recommended_quantity' => (int) $r->recommended_quantity,
                'days_until_stockout' => (int) $r->days_until_stockout,
                'avg_daily_sales' => (float) $r->avg_daily_sales,
            ];
        })->values()->all();

        $analyticsRow = $dssInsights['analytics'];
        $monthlySnapshot = null;
        if ($analyticsRow) {
            $monthlySnapshot = [
                'period_label' => $analyticsRow->analysis_date?->format('F Y'),
                'total_orders' => (int) $analyticsRow->total_orders,
                'total_revenue' => (float) $analyticsRow->total_revenue,
                'aov' => (float) $analyticsRow->average_order_value,
                'top_products' => $analyticsRow->top_products ?? [],
            ];
        }

        $expiredInStock = (int) Inventory::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->count();

        $expiringWithin30 = collect($expiryAlerts)->filter(fn ($a) => ($a['days_until_expiry'] ?? 999) < 30)->count();
        $stockoutRiskCount = collect($recommendations)->filter(
            fn ($r) => ($r['days_until_stockout'] ?? 999) <= 5 && ($r['days_until_stockout'] ?? -1) >= 0
        )->count();

        $alertCenter = $this->buildAlertCenterBanners(
            $expiredInStock,
            $expiringWithin30,
            $stockoutRiskCount
        );

        $unitsLast30 = (int) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.distributor_id', $distributor->id)
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.updated_at', [now()->copy()->subDays(30)->startOfDay(), now()->endOfDay()])
            ->sum('order_items.quantity');

        $qtyTotal = (int) ($invAgg->qty_total ?? 0);
        $dailyBurn = $unitsLast30 > 0 ? $unitsLast30 / 30.0 : 0.0;
        $daysCover = $dailyBurn > 0 ? $qtyTotal / $dailyBurn : null;
        $inventoryGauge = $this->buildInventoryGauge($qtyTotal, $daysCover);

        $newestOrderId = (int) (Order::where('distributor_id', $distributor->id)->max('id') ?? 0);

        return Inertia::render('Owner/Dashboard', [
            'distributor' => $distributor,
            'canViewFinancials' => $canViewFinancials,
            'filters' => [
                'trend_scope' => $trendScope,
                'revenue' => [
                    'from' => $revFrom->toDateString(),
                    'to' => $revTo->toDateString(),
                    'label' => $revLabel,
                ],
                'new_orders' => [
                    'from' => $ordFrom->toDateString(),
                    'to' => $ordTo->toDateString(),
                    'label' => $ordLabel,
                ],
                'top_products' => [
                    'from' => $topFrom->toDateString(),
                    'to' => $topTo->toDateString(),
                    'label' => $topLabel,
                    'metric' => $topMetric,
                ],
                'summary' => [
                    'from' => $sumFrom->toDateString(),
                    'to' => $sumTo->toDateString(),
                    'label' => $sumLabel,
                ],
            ],
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'processingOrders' => $processingOrders,
                'ordersPlacedInRange' => $ordersPlacedInRange,
                'revenueInRange' => $revenueInRange,
                'totalRevenue' => $totalRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'revenueMtd' => $revenueMtd,
                'ordersMtd' => $ordersMtd,
                'totalProducts' => $totalProducts,
                'lowStockCount' => $lowStockCount,
            ],
            'financial_kpis' => [
                'revenue_mtd' => $revenueMtd,
                'revenue_target' => null,
                'gross_margin_pct' => null,
            ],
            'alert_center' => $alertCenter,
            'analytics' => [
                'velocity_heatmap' => $velocityHeatmap,
                'demand_forecast' => $demandForecast,
                'inventory_gauge' => $inventoryGauge,
                'units_sold_30d' => $unitsLast30,
            ],
            'pulse_baseline' => [
                'newest_order_id' => $newestOrderId,
            ],
            'recentOrders' => $recentOrders,
            'inventory_alerts' => [
                'expiring_batches' => $expiryAlerts,
                'low_stock_rows' => $lowStockAlerts,
            ],
            'unreadAlerts' => DssAlert::where('distributor_id', $distributor->id)
                ->where('is_read', false)
                ->latest()
                ->limit(15)
                ->get(),
            'charts' => [
                'revenue_series' => $revenueSeries,
                'revenue_period_label' => $revLabel,
                'orders_series' => $ordersSeries,
                'orders_period_label' => $ordLabel,
                'top_products' => $topProducts,
                'top_metric' => $topMetric,
                'top_period_label' => $topLabel,
            ],
            'order_pipeline' => $orderPipeline,
            'inventory_pulse' => [
                'quantity_total' => (int) ($invAgg->qty_total ?? 0),
                'reserved_total' => (int) ($invAgg->res_total ?? 0),
            ],
            'restock_insights' => [
                'recommendations' => $recommendations,
                'monthly_snapshot' => $monthlySnapshot,
                'pending_recommendations' => (int) ($dssInsights['counts']['pending_recommendations'] ?? 0),
                'expiring_batches_count' => count($expiryAlerts),
                'low_stock_rows_count' => count($lowStockAlerts),
            ],
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    protected function parseRange(Request $request, string $fromKey, string $toKey, int $defaultSpanDays): array
    {
        $to = $request->filled($toKey)
            ? Carbon::parse($request->input($toKey))->endOfDay()
            : now()->endOfDay();
        $from = $request->filled($fromKey)
            ? Carbon::parse($request->input($fromKey))->startOfDay()
            : $to->copy()->subDays($defaultSpanDays)->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->subDays($defaultSpanDays)->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, $this->formatRangeLabel($from, $to)];
    }

    protected function formatRangeLabel(Carbon $from, Carbon $to): string
    {
        return $from->format('M j, Y').' – '.$to->format('M j, Y');
    }

    /**
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    protected function buildDateBuckets(Carbon $from, Carbon $to): array
    {
        $days = $from->diffInDays($to) + 1;
        $buckets = [];

        if ($days <= 31) {
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $buckets[] = [
                    'label' => $d->format('D, M j'),
                    'start' => $d->copy()->startOfDay(),
                    'end' => $d->copy()->endOfDay(),
                ];
            }

            return $buckets;
        }

        if ($days <= 120) {
            $cursor = $from->copy()->startOfDay();
            while ($cursor->lte($to)) {
                $start = $cursor->copy();
                $end = $cursor->copy()->addDays(6)->endOfDay();
                if ($end->gt($to)) {
                    $end = $to->copy()->endOfDay();
                }
                $buckets[] = [
                    'label' => $start->format('M j').' – '.$end->format('M j'),
                    'start' => $start->copy()->startOfDay(),
                    'end' => $end,
                ];
                $cursor = $end->copy()->addDay()->startOfDay();
            }

            return $buckets;
        }

        $cursor = $from->copy()->startOfMonth();
        while ($cursor->lte($to)) {
            $start = $cursor->copy();
            if ($start->lt($from)) {
                $start = $from->copy();
            }
            $end = $cursor->copy()->endOfMonth();
            if ($end->gt($to)) {
                $end = $to->copy();
            }
            $buckets[] = [
                'label' => $cursor->format('M Y'),
                'start' => $start->copy()->startOfDay(),
                'end' => $end->copy()->endOfDay(),
            ];
            $cursor->addMonth()->startOfMonth();
        }

        return $buckets;
    }

    /**
     * @return list<array{key: string, label: string, description: string, count: int, href: string}>
     */
    protected function buildOrderPipeline(int $distributorId): array
    {
        $counts = Order::where('distributor_id', $distributorId)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $defs = [
            ['key' => 'pending', 'label' => 'Needs your review', 'description' => 'New orders waiting for approval', 'href' => '/owner/orders?status=pending', 'always' => true],
            ['key' => 'approved', 'label' => 'Approved', 'description' => 'Ready to pack', 'href' => '/owner/orders?status=approved', 'always' => true],
            ['key' => 'packed', 'label' => 'Packed', 'description' => 'Waiting for pickup or shipment', 'href' => '/owner/orders?status=packed', 'always' => true],
            ['key' => 'shipped', 'label' => 'Out for delivery', 'description' => 'With courier or on the way', 'href' => '/owner/orders?status=shipped', 'always' => true],
            ['key' => 'delivered', 'label' => 'Delivered', 'description' => 'Marked delivered', 'href' => '/owner/orders?status=delivered', 'always' => false],
            ['key' => 'completed', 'label' => 'Completed', 'description' => 'Closed / completed', 'href' => '/owner/orders?status=completed', 'always' => false],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'description' => 'Cancelled orders', 'href' => '/owner/orders?status=cancelled', 'always' => false],
            ['key' => 'rejected', 'label' => 'Rejected', 'description' => 'Rejected or declined', 'href' => '/owner/orders?status=rejected', 'always' => false],
        ];

        $out = [];
        foreach ($defs as $def) {
            $c = (int) ($counts[$def['key']] ?? 0);
            if ($def['always'] || $c > 0) {
                $out[] = [
                    'key' => $def['key'],
                    'label' => $def['label'],
                    'description' => $def['description'],
                    'count' => $c,
                    'href' => $def['href'],
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{id: int, name: string, value: float|int, value_label: string}>
     */
    protected function buildTopProducts(
        int $distributorId,
        array $paidStatuses,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $metric,
        bool $canViewFinancials
    ): array {
        if ($metric === 'revenue' && ! $canViewFinancials) {
            $metric = 'units';
        }

        $q = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.distributor_id', $distributorId)
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo]);

        if ($metric === 'revenue') {
            $rows = (clone $q)
                ->whereIn('orders.status', $paidStatuses)
                ->selectRaw('products.id, products.name, SUM(COALESCE(order_items.subtotal, order_items.total_price, 0)) as metric_value')
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('metric_value')
                ->limit(8)
                ->get();
        } elseif ($metric === 'units') {
            $rows = (clone $q)
                ->whereIn('orders.status', $paidStatuses)
                ->selectRaw('products.id, products.name, SUM(order_items.quantity) as metric_value')
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('metric_value')
                ->limit(8)
                ->get();
        } else {
            $rows = (clone $q)
                ->whereNotIn('orders.status', ['cancelled', 'rejected'])
                ->selectRaw('products.id, products.name, COUNT(DISTINCT orders.id) as metric_value')
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('metric_value')
                ->limit(8)
                ->get();
        }

        return $rows->map(function ($row) use ($metric) {
            $v = $row->metric_value;
            if ($metric === 'revenue') {
                $label = '₱'.number_format((float) $v, 0);
            } elseif ($metric === 'orders') {
                $label = ((int) $v).' '.(((int) $v === 1) ? 'order' : 'orders');
            } else {
                $label = ((int) $v).' '.(((int) $v === 1) ? 'unit' : 'units');
            }

            return [
                'id' => (int) $row->id,
                'name' => $row->name,
                'value' => $metric === 'revenue' ? (float) $v : (int) $v,
                'value_label' => $label,
            ];
        })->values()->all();
    }

    public function pulse(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        if (! $distributor) {
            return response()->json(['error' => 'no_distributor'], 403);
        }

        $newest = Order::where('distributor_id', $distributor->id)
            ->orderByDesc('id')
            ->first(['id', 'order_number', 'created_at']);

        return response()->json([
            'pending_orders' => (int) Order::where('distributor_id', $distributor->id)->where('status', 'pending')->count(),
            'processing_orders' => (int) Order::where('distributor_id', $distributor->id)
                ->whereIn('status', ['approved', 'packed', 'shipped'])
                ->count(),
            'newest_order_id' => $newest?->id ?? 0,
            'newest_order_number' => $newest?->order_number,
            'newest_created_at' => $newest?->created_at?->toIso8601String(),
        ]);
    }

    /**
     * @param  list<int>  $lowStockProductIds
     * @return array<string, mixed>
     */
    protected function formatOrderForDashboard(Order $order, array $lowStockProductIds): array
    {
        $lowSet = array_flip($lowStockProductIds);
        $critical = false;

        foreach ($order->items as $item) {
            $p = $item->product;
            if (! $p) {
                continue;
            }
            $hay = strtolower(trim(($p->name ?? '').' '.(optional($p->category)->name ?? '')));
            if (preg_match('/\b(ventilator|defibrillator|aed\b|infusion\s*pump|dialysis|pacemaker|\bicu\b|resuscitat|emergency|critical\s*care|life\s*support)\b/i', $hay)) {
                $critical = true;
                break;
            }
        }

        $ageHours = $order->created_at->diffInHours(now());
        $openish = in_array($order->status, ['pending', 'approved', 'packed'], true);
        $delayed = $openish && $ageHours >= 24;

        $touchesLowStock = false;
        $productIds = [];
        foreach ($order->items as $item) {
            if ($item->product_id) {
                $productIds[] = (int) $item->product_id;
                if (isset($lowSet[$item->product_id])) {
                    $touchesLowStock = true;
                }
            }
        }
        $productIds = array_values(array_unique($productIds));

        $score = 1;
        $tier = 'routine';
        $label = 'Routine consumables / general';

        if ($critical && $delayed) {
            $score = 10;
            $tier = 'critical';
            $label = 'Critical-care line · open ≥24h';
        } elseif ($critical && in_array($order->status, ['pending', 'approved'], true)) {
            $score = 7;
            $tier = 'high';
            $label = 'Critical-care line · needs dispatch';
        } elseif ($critical) {
            $score = 5;
            $tier = 'elevated';
            $label = 'Critical-care line';
        } elseif ($touchesLowStock) {
            $score = 3;
            $tier = 'supply';
            $label = 'Includes below-reorder SKU';
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'prescription_status' => $order->prescription_status,
            'total_amount' => (float) $order->total_amount,
            'created_at' => $order->created_at->toIso8601String(),
            'customer' => $order->customer ? ['id' => $order->customer->id, 'name' => $order->customer->name] : null,
            'product_ids' => $productIds,
            'priority_score' => $score,
            'priority_tier' => $tier,
            'priority_label' => $label,
        ];
    }

    /**
     * @return list<array{level: string, title: string, body: string, href: string, action?: string}>
     */
    protected function buildAlertCenterBanners(int $expiredInStock, int $expiringWithin30, int $stockoutRiskCount): array
    {
        $banners = [];

        if ($expiredInStock > 0) {
            $banners[] = [
                'level' => 'critical',
                'title' => 'Expired stock detected',
                'body' => $expiredInStock.' stocked item'.($expiredInStock === 1 ? '' : 's').' are past expiry — quarantine or dispose per hospital policy.',
                'href' => '/owner/inventory', // Directs to the actual inventory page
                'action' => 'Manage Inventory', 
            ];
        }

        if ($expiringWithin30 > 0) {
            $banners[] = [
                'level' => 'critical',
                'title' => 'Items expiring soon (Under 30 days)',
                'body' => $expiringWithin30.' batch'.($expiringWithin30 === 1 ? '' : 'es').' need to be rotated or cleared out before they expire.',
                'href' => '/owner/inventory', 
                'action' => 'Check Expiry Dates',
            ];
        }

        if ($stockoutRiskCount > 0) {
            $banners[] = [
                'level' => 'warning',
                'title' => 'Predicted stockout (≤5 days)',
                'body' => $stockoutRiskCount.' product'.($stockoutRiskCount === 1 ? '' : 's').' may run out soon based on recent sales — prioritize restocking.',
                'href' => '/owner/inventory',
                'action' => 'Restock Items',
            ];
        }

        return $banners;
    }

    /**
     * @return array{week_labels: list<string>, rows: list<array{product_id: int, name: string, cells: list<float>, units: list<int>}>}
     */
    protected function buildVelocityHeatmap(int $distributorId, array $paidStatuses): array
    {
        $weeks = 6;
        $end = now()->copy()->endOfDay();
        $start = now()->copy()->subWeeks($weeks)->startOfWeek();

        $topIds = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.distributor_id', $distributorId)
            ->whereIn('orders.status', $paidStatuses)
            ->whereBetween('orders.updated_at', [$start, $end])
            ->selectRaw('products.id as pid, products.name as pname, SUM(order_items.quantity) as u')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('u')
            ->limit(10)
            ->get();

        if ($topIds->isEmpty()) {
            return ['week_labels' => [], 'rows' => []];
        }

        $weekBuckets = [];
        for ($i = 0; $i < $weeks; $i++) {
            $ws = $start->copy()->addWeeks($i)->startOfWeek();
            $we = $ws->copy()->endOfWeek();
            if ($we->gt($end)) {
                $we = $end->copy();
            }
            $weekBuckets[] = [
                'label' => $ws->format('M j'),
                'start' => $ws->copy(),
                'end' => $we,
            ];
        }

        $rows = [];
        foreach ($topIds as $row) {
            $pid = (int) $row->pid;
            $cells = [];
            foreach ($weekBuckets as $b) {
                $cells[] = (int) OrderItem::query()
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.distributor_id', $distributorId)
                    ->whereIn('orders.status', $paidStatuses)
                    ->where('order_items.product_id', $pid)
                    ->whereBetween('orders.updated_at', [$b['start'], $b['end']])
                    ->sum('order_items.quantity');
            }
            $max = max($cells) ?: 1;
            $norm = array_map(fn (int $c) => round($c / $max, 4), $cells);

            $rows[] = [
                'product_id' => $pid,
                'name' => $row->pname,
                'cells' => $norm,
                'units' => $cells,
            ];
        }

        return [
            'week_labels' => array_map(fn ($b) => $b['label'], $weekBuckets),
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     * mode: string,
     * value_label: string,
     * labels: list<string>,
     * actual: list<float|int|null>,
     * projected: list<float|int|null>,
     * comparison: list<float|int|null>|null,
     * comparison_label: string|null
     * }
     */
    protected function buildDemandForecast(int $distributorId, array $paidStatuses, bool $canViewFinancials, string $scope): array
    {
        if ($scope === 'yoy') {
            return $this->buildDemandForecastYoy($distributorId, $paidStatuses, $canViewFinancials);
        }

        $now = now()->endOfDay();
        $from = now()->copy()->startOfMonth()->startOfDay();
        $labels = [];
        $actual = [];

        for ($d = $from->copy(); $d->lte($now); $d->addDay()) {
            $labels[] = $d->format('M j');
            $dayStart = $d->copy()->startOfDay();
            $dayEnd = $d->copy()->endOfDay();
            if ($canViewFinancials) {
                $actual[] = (float) Order::where('distributor_id', $distributorId)
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('updated_at', [$dayStart, $dayEnd])
                    ->sum('total_amount');
            } else {
                $actual[] = (int) Order::where('distributor_id', $distributorId)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count();
            }
        }

        $last7 = array_slice($actual, -7);
        $ma = count($last7) ? array_sum($last7) / count($last7) : 0.0;

        $projectedHead = array_fill(0, count($actual), null);
        $futureLabels = [];
        $futureProj = [];
        for ($i = 1; $i <= 14; $i++) {
            $futureLabels[] = $now->copy()->startOfDay()->addDays($i)->format('M j');
            $futureProj[] = round($ma, 2);
        }

        return [
            'mode' => 'mtd',
            'value_label' => $canViewFinancials ? 'Paid revenue (₱)' : 'Orders placed',
            'labels' => array_merge($labels, $futureLabels),
            'actual' => array_merge($actual, array_fill(0, count($futureLabels), null)),
            'projected' => array_merge($projectedHead, $futureProj),
            'comparison' => null,
            'comparison_label' => null,
        ];
    }

    /**
     * @return array{
     * mode: string,
     * value_label: string,
     * labels: list<string>,
     * actual: list<float|int|null>,
     * projected: list<float|int|null>,
     * comparison: list<float|int|null>|null,
     * comparison_label: string|null
     * }
     */
    protected function buildDemandForecastYoy(int $distributorId, array $paidStatuses, bool $canViewFinancials): array
    {
        $labels = [];
        $ty = [];
        $ly = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->copy()->subMonths($i)->startOfMonth();
            $labels[] = $month->format('M Y');
            $mStart = $month->copy();
            $mEnd = $month->copy()->endOfMonth();

            if ($canViewFinancials) {
                $ty[] = (float) Order::where('distributor_id', $distributorId)
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('updated_at', [$mStart, $mEnd])
                    ->sum('total_amount');
                $lyMonth = $month->copy()->subYear();
                $ly[] = (float) Order::where('distributor_id', $distributorId)
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('updated_at', [$lyMonth->copy()->startOfMonth(), $lyMonth->copy()->endOfMonth()])
                    ->sum('total_amount');
            } else {
                $ty[] = (int) Order::where('distributor_id', $distributorId)
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->count();
                $lyMonth = $month->copy()->subYear();
                $ly[] = (int) Order::where('distributor_id', $distributorId)
                    ->whereBetween('created_at', [$lyMonth->copy()->startOfMonth(), $lyMonth->copy()->endOfMonth()])
                    ->count();
            }
        }

        $last3 = array_slice($ty, -3);
        $nextProj = count($last3) ? array_sum($last3) / count($last3) : 0.0;

        $projPad = array_fill(0, count($labels), null);
        $extendedLabels = array_merge($labels, ['Next month (est.)']);
        $actualExtended = array_merge($ty, [null]);
        $comparisonExtended = array_merge($ly, [null]);
        $projectedExtended = array_merge($projPad, [round($nextProj, 2)]);

        return [
            'mode' => 'yoy',
            'value_label' => $canViewFinancials ? 'Paid revenue (₱)' : 'Orders placed',
            'labels' => $extendedLabels,
            'actual' => $actualExtended,
            'projected' => $projectedExtended,
            'comparison' => $comparisonExtended,
            'comparison_label' => $canViewFinancials ? 'Same month, prior year' : 'Same month, prior year (orders)',
        ];
    }

    /**
     * @return array{needle: float, zone: string, headline: string, days_cover: float|null, daily_burn: float}
     */
    protected function buildInventoryGauge(int $qtyTotal, ?float $daysCover): array
    {
        if ($qtyTotal <= 0 || $daysCover === null) {
            return [
                'needle' => 50,
                'zone' => 'neutral',
                'headline' => 'Not enough movement data',
                'days_cover' => $daysCover,
                'daily_burn' => 0.0,
            ];
        }

        $needle = 50.0;
        if ($daysCover < 7) {
            $needle = 8 + ($daysCover / 7) * 22;
        } elseif ($daysCover <= 45) {
            $needle = 30 + (($daysCover - 7) / 38) * 40;
        } else {
            $needle = 70 + min(28.0, ($daysCover - 45) / 75 * 28);
        }
        $needle = min(100, max(0, $needle));

        $zone = 'balanced';
        $headline = 'Coverage looks balanced';
        if ($daysCover < 14) {
            $zone = 'understock';
            $headline = 'Lean coverage — stockout risk';
        } elseif ($daysCover > 90) {
            $zone = 'overstock';
            $headline = 'High cover — capital tied up';
        }

        return [
            'needle' => round($needle, 1),
            'zone' => $zone,
            'headline' => $headline,
            'days_cover' => round($daysCover, 1),
            'daily_burn' => $qtyTotal > 0 && $daysCover > 0 ? round($qtyTotal / $daysCover, 2) : 0.0,
        ];
    }
}