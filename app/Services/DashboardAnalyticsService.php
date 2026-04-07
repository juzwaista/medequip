<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardAnalyticsService
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function parseRange(Request $request, string $fromKey, string $toKey, int $defaultSpanDays): array
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

    public function formatRangeLabel(Carbon $from, Carbon $to): string
    {
        return $from->format('M j, Y').' – '.$to->format('M j, Y');
    }

    /**
     * Verified, non-refunded payments for this shop (net seller share after platform fee).
     */
    public function verifiedSellerPaymentsForDistributor(int $distributorId)
    {
        return Payment::query()
            ->whereHas('invoice.order', fn ($q) => $q->where('distributor_id', $distributorId))
            ->where('status', 'verified')
            ->whereNotIn('escrow_status', ['refunded'])
            ->whereNotNull('verified_at');
    }

    public function sumVerifiedNetSellerForDistributor(int $distributorId, Carbon $from, Carbon $to): float
    {
        return (float) $this->verifiedSellerPaymentsForDistributor($distributorId)
            ->whereBetween('verified_at', [$from, $to])
            ->sum('net_seller_amount');
    }

    /**
     * @return list<array{label: string, start: Carbon, end: Carbon}>
     */
    public function buildDateBuckets(Carbon $from, Carbon $to): array
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
    public function buildOrderPipeline(int $distributorId): array
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
    public function buildTopProducts(
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

    /**
     * @param  list<int>  $lowStockProductIds
     * @return array<string, mixed>
     */
    public function formatOrderForDashboard(Order $order, array $lowStockProductIds): array
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
    public function buildAlertCenterBanners(int $expiredInStock, int $expiringSoonCount, int $stockoutRiskCount, int $expiryWarningDays = 60): array
    {
        $banners = [];
        $expiryWarningDays = max(1, min(365, $expiryWarningDays));

        if ($expiredInStock > 0) {
            $banners[] = [
                'level' => 'critical',
                'title' => 'Expired stock detected',
                'body' => $expiredInStock.' stocked line'.($expiredInStock === 1 ? '' : 's').' are past expiry — quarantine or dispose per policy.',
                'href' => '/owner/inventory?filter=expired',
                'action' => 'Open queue',
            ];
        }

        if ($expiringSoonCount > 0) {
            $banners[] = [
                'level' => 'critical',
                'title' => 'Expiry within '.$expiryWarningDays.' days',
                'body' => $expiringSoonCount.' batch'.($expiringSoonCount === 1 ? '' : 'es').' are inside your DSS expiry window — rotate or clear before they expire.',
                'href' => '/owner/inventory?filter=expiring',
                'action' => 'Review batches',
            ];
        }

        if ($stockoutRiskCount > 0) {
            $banners[] = [
                'level' => 'warning',
                'title' => 'Predicted stockout (≤5 days)',
                'body' => $stockoutRiskCount.' SKU'.($stockoutRiskCount === 1 ? '' : 's').' may run out at the current burn rate — prioritize inbound or transfers.',
                'href' => '/owner/inventory?filter=predicted_stockout',
                'action' => 'Inventory',
            ];
        }

        return $banners;
    }

    /**
     * @return array{week_labels: list<string>, rows: list<array{product_id: int, name: string, cells: list<float>, units: list<int>}>}
     */
    public function buildVelocityHeatmap(int $distributorId, array $paidStatuses): array
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

        $pidList = $topIds->pluck('pid')->all();

        $weekExpr = \DB::connection()->getDriverName() === 'mysql'
            ? 'YEARWEEK(orders.updated_at, 1)'
            : "strftime('%Y-%W', orders.updated_at)";

        $raw = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.distributor_id', $distributorId)
            ->whereIn('orders.status', $paidStatuses)
            ->whereIn('order_items.product_id', $pidList)
            ->whereBetween('orders.updated_at', [$start, $end])
            ->selectRaw("order_items.product_id as pid, {$weekExpr} as yw, SUM(order_items.quantity) as qty")
            ->groupBy('order_items.product_id', \DB::raw($weekExpr))
            ->get();

        $bucketYws = [];
        foreach ($weekBuckets as $b) {
            $mid = $b['start']->copy()->addDays(3);
            $bucketYws[] = \DB::connection()->getDriverName() === 'mysql'
                ? $mid->format('oW')
                : $mid->format('Y-W');
        }

        $lookup = [];
        foreach ($raw as $r) {
            $lookup[(int) $r->pid][$r->yw] = (int) $r->qty;
        }

        $rows = [];
        foreach ($topIds as $row) {
            $pid = (int) $row->pid;
            $cells = [];
            foreach ($bucketYws as $yw) {
                $cells[] = $lookup[$pid][$yw] ?? 0;
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
     *     mode: string,
     *     value_label: string,
     *     labels: list<string>,
     *     actual: list<float|int|null>,
     *     projected: list<float|int|null>,
     *     comparison: list<float|int|null>|null,
     *     comparison_label: string|null
     * }
     */
    public function buildDemandForecast(int $distributorId, array $paidStatuses, bool $canViewFinancials, string $scope): array
    {
        if ($scope === 'yoy') {
            return $this->buildDemandForecastYoy($distributorId, $paidStatuses, $canViewFinancials);
        }

        $now = now()->endOfDay();
        $from = now()->copy()->startOfMonth()->startOfDay();

        $labels = [];
        $dayMap = [];
        for ($d = $from->copy(); $d->lte($now); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $labels[] = $d->format('M j');
            $dayMap[$key] = 0;
        }

        if ($canViewFinancials) {
            $dateExpr = \DB::connection()->getDriverName() === 'mysql'
                ? 'DATE(verified_at)'
                : 'date(verified_at)';

            $rows = $this->verifiedSellerPaymentsForDistributor($distributorId)
                ->whereBetween('verified_at', [$from, $now])
                ->selectRaw("{$dateExpr} as d, SUM(net_seller_amount) as total")
                ->groupBy(\DB::raw($dateExpr))
                ->pluck('total', 'd');

            foreach ($rows as $d => $total) {
                if (isset($dayMap[$d])) {
                    $dayMap[$d] = (float) $total;
                }
            }
        } else {
            $dateExpr = \DB::connection()->getDriverName() === 'mysql'
                ? 'DATE(created_at)'
                : 'date(created_at)';

            $rows = Order::where('distributor_id', $distributorId)
                ->whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$from, $now])
                ->selectRaw("{$dateExpr} as d, COUNT(*) as c")
                ->groupBy(\DB::raw($dateExpr))
                ->pluck('c', 'd');

            foreach ($rows as $d => $c) {
                if (isset($dayMap[$d])) {
                    $dayMap[$d] = (int) $c;
                }
            }
        }

        $actual = array_values($dayMap);
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
            'value_label' => $canViewFinancials ? 'Net proceeds (₱) · verified payments' : 'Orders placed',
            'labels' => array_merge($labels, $futureLabels),
            'actual' => array_merge($actual, array_fill(0, count($futureLabels), null)),
            'projected' => array_merge($projectedHead, $futureProj),
            'comparison' => null,
            'comparison_label' => null,
        ];
    }

    /**
     * @return array{
     *     mode: string,
     *     value_label: string,
     *     labels: list<string>,
     *     actual: list<float|int|null>,
     *     projected: list<float|int|null>,
     *     comparison: list<float|int|null>|null,
     *     comparison_label: string|null
     * }
     */
    public function buildDemandForecastYoy(int $distributorId, array $paidStatuses, bool $canViewFinancials): array
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
                $ty[] = $this->sumVerifiedNetSellerForDistributor($distributorId, $mStart, $mEnd);
                $lyMonth = $month->copy()->subYear();
                $lyStart = $lyMonth->copy()->startOfMonth();
                $lyEnd = $lyMonth->copy()->endOfMonth();
                $ly[] = $this->sumVerifiedNetSellerForDistributor($distributorId, $lyStart, $lyEnd);
            } else {
                $ty[] = (int) Order::where('distributor_id', $distributorId)
                    ->whereIn('status', $paidStatuses)
                    ->whereBetween('created_at', [$mStart, $mEnd])
                    ->count();
                $lyMonth = $month->copy()->subYear();
                $ly[] = (int) Order::where('distributor_id', $distributorId)
                    ->whereIn('status', $paidStatuses)
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
            'value_label' => $canViewFinancials ? 'Net proceeds (₱) · verified payments' : 'Orders placed',
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
    public function buildInventoryGauge(int $qtyTotal, ?float $daysCover): array
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

    /**
     * Automated DSS risk assessment (on-the-fly).
     *
     * @return array{reasons: list<string>, level: string}|null
     */
    public function assessDssRisk(int $distributorId): ?array
    {
        $dssWarning = null;
        $riskScore = 0;
        $dssReasons = [];
        $now = now();
        $thirtyDaysAgo = now()->copy()->subDays(30);

        $recentOrdersCount = Order::where('distributor_id', $distributorId)->where('created_at', '>=', $thirtyDaysAgo)->count();
        if ($recentOrdersCount > 0) {
            $cancelledCount = Order::where('distributor_id', $distributorId)->where('created_at', '>=', $thirtyDaysAgo)->whereIn('status', ['cancelled', 'rejected'])->count();
            if (($cancelledCount / $recentOrdersCount) > 0.15) {
                $dssReasons[] = 'High Cancellation Rate (>15% in last 30 days)';
                $riskScore += 2;
            }
        }

        $stalePendingCount = Order::where('distributor_id', $distributorId)->where('status', 'pending')->where('created_at', '<', $now->copy()->subHours(48))->count();
        if ($stalePendingCount > 0) {
            $dssReasons[] = "{$stalePendingCount} order(s) pending approval for over 48 hours";
            $riskScore += ($stalePendingCount >= 5) ? 3 : 1;
        }

        $activeProductsCount = Product::where('distributor_id', $distributorId)->where('is_active', true)->count();
        if ($activeProductsCount > 0) {
            $totalInventory = Inventory::whereHas('product', function ($q) use ($distributorId) {
                $q->where('distributor_id', $distributorId)->where('is_active', true);
            })->sum('quantity');
            if ($totalInventory == 0) {
                $dssReasons[] = 'All active products are completely out of stock';
                $riskScore += 1;
            }
        }

        if ($riskScore > 0) {
            $level = $riskScore >= 4 ? 'Critical' : ($riskScore >= 2 ? 'High' : 'Medium');
            $dssWarning = [
                'reasons' => $dssReasons,
                'level' => $level,
            ];

            $distributor = \App\Models\Distributor::find($distributorId);
            if ($distributor && $distributor->user) {
                $alreadySent = $distributor->user->notifications()
                    ->where('type', \App\Notifications\AutomatedAccountWarningNotification::class)
                    ->where('created_at', '>=', now()->subDay())
                    ->exists();

                if (! $alreadySent) {
                    $distributor->user->notify(new \App\Notifications\AutomatedAccountWarningNotification($level, $dssReasons));
                }
            }
        }

        return $dssWarning;
    }
}
