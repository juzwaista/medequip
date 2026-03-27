<?php

namespace App\Services;

use App\Models\DssAlert;
use App\Models\DssDistributorSettings;
use App\Models\DssReorderRecommendation;
use App\Models\DssSalesAnalytics;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

class DssEngineService
{
    public function syncForDistributor(int $distributorId): void
    {
        $settings = DssDistributorSettings::firstOrCreate(
            ['distributor_id' => $distributorId],
            [
                'low_stock_threshold_days' => 7,
                'expiry_warning_days' => 60,
                'dead_stock_days' => 90,
                'enable_auto_alerts' => true,
            ]
        );

        if ($settings->enable_auto_alerts) {
            $this->syncAlerts($distributorId, (int) $settings->expiry_warning_days);
        }

        $this->syncReorderRecommendations($distributorId, (int) $settings->low_stock_threshold_days);
        $this->syncSalesAnalytics($distributorId);
    }

    public function getInsights(int $distributorId): array
    {
        $settings = DssDistributorSettings::firstOrCreate(
            ['distributor_id' => $distributorId],
            [
                'low_stock_threshold_days' => 7,
                'expiry_warning_days' => 60,
                'dead_stock_days' => 90,
                'enable_auto_alerts' => true,
            ]
        );

        $alerts = DssAlert::where('distributor_id', $distributorId)
            ->latest()
            ->limit(30)
            ->get();

        $recommendations = DssReorderRecommendation::with(['product', 'branch'])
            ->where('distributor_id', $distributorId)
            ->where('is_actioned', false)
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->limit(20)
            ->get();

        $latestAnalytics = DssSalesAnalytics::where('distributor_id', $distributorId)
            ->where('period_type', 'monthly')
            ->latest('analysis_date')
            ->first();

        return [
            'settings' => $settings,
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'analytics' => $latestAnalytics,
            'counts' => [
                'unread_alerts' => $alerts->where('is_read', false)->count(),
                'critical_alerts' => $alerts->where('severity', 'critical')->count(),
                'pending_recommendations' => $recommendations->count(),
            ],
        ];
    }

    protected function syncAlerts(int $distributorId, int $expiryWarningDays): void
    {
        DssAlert::where('distributor_id', $distributorId)
            ->where('is_read', false)
            ->whereIn('alert_type', ['expiry_warning', 'low_stock'])
            ->delete();

        $expiryInventories = Inventory::with('product')
            ->whereHas('product', fn($q) => $q->where('distributor_id', $distributorId))
            ->whereNotNull('expiry_date')
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '<=', now()->addDays($expiryWarningDays))
            ->whereDate('expiry_date', '>', now())
            ->get();

        foreach ($expiryInventories as $inventory) {
            $days = now()->diffInDays($inventory->expiry_date);
            DssAlert::create([
                'distributor_id' => $distributorId,
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'alert_type' => 'expiry_warning',
                'severity' => $days <= 15 ? 'critical' : 'warning',
                'title' => 'Product Expiring Soon',
                'message' => "{$inventory->product->name} batch {$inventory->batch_number} expires in {$days} day(s).",
                'metadata' => [
                    'expiry_date' => optional($inventory->expiry_date)->format('Y-m-d'),
                    'quantity' => $inventory->quantity,
                ],
                'is_read' => false,
            ]);
        }

        $lowStockInventories = Inventory::with('product')
            ->whereHas('product', fn($q) => $q->where('distributor_id', $distributorId))
            ->whereRaw('quantity <= reorder_level')
            ->get();

        foreach ($lowStockInventories as $inventory) {
            DssAlert::create([
                'distributor_id' => $distributorId,
                'inventory_id' => $inventory->id,
                'product_id' => $inventory->product_id,
                'alert_type' => 'low_stock',
                'severity' => $inventory->quantity <= 0 ? 'critical' : 'warning',
                'title' => 'Low Stock Alert',
                'message' => "{$inventory->product->name} stock is {$inventory->quantity}, reorder level is {$inventory->reorder_level}.",
                'metadata' => [
                    'current_stock' => $inventory->quantity,
                    'reorder_level' => $inventory->reorder_level,
                ],
                'is_read' => false,
            ]);
        }
    }

    protected function syncReorderRecommendations(int $distributorId, int $targetDaysCover): void
    {
        DssReorderRecommendation::where('distributor_id', $distributorId)
            ->where('is_actioned', false)
            ->delete();

        $salesByProduct = OrderItem::query()
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) as qty_30d')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.distributor_id', $distributorId)
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->groupBy('order_items.product_id')
            ->pluck('qty_30d', 'product_id');

        $products = Product::with('inventory')
            ->where('distributor_id', $distributorId)
            ->get();

        foreach ($products as $product) {
            $avgDailySales = round(((float) ($salesByProduct[$product->id] ?? 0)) / 30, 2);
            if ($avgDailySales <= 0) {
                continue;
            }

            foreach ($product->inventory as $inventory) {
                $available = (int) $inventory->availableStock;
                $daysUntilStockout = $available > 0 ? (int) floor($available / max($avgDailySales, 0.01)) : 0;

                if ($daysUntilStockout > max(14, $targetDaysCover)) {
                    continue;
                }

                $recommended = (int) max(1, ceil(($avgDailySales * $targetDaysCover * 1.5) - $available));
                $priority = $daysUntilStockout <= 3 ? 'high' : ($daysUntilStockout <= 7 ? 'medium' : 'low');

                DssReorderRecommendation::create([
                    'distributor_id' => $distributorId,
                    'product_id' => $product->id,
                    'branch_id' => $inventory->branch_id,
                    'current_stock' => $available,
                    'recommended_quantity' => $recommended,
                    'avg_daily_sales' => $avgDailySales,
                    'days_until_stockout' => $daysUntilStockout,
                    'priority' => $priority,
                    'is_actioned' => false,
                ]);
            }
        }
    }

    protected function syncSalesAnalytics(int $distributorId): void
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $orders = \App\Models\Order::where('distributor_id', $distributorId)
            ->whereIn('status', ['delivered', 'completed'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = (float) $orders->sum('total_amount');
        $averageOrderValue = $totalOrders > 0 ? ($totalRevenue / $totalOrders) : 0;

        $topProducts = OrderItem::query()
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as sold_qty')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.distributor_id', $distributorId)
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->whereBetween('orders.created_at', [$periodStart, $periodEnd])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sold_qty')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'product_id' => $row->id,
                'name' => $row->name,
                'sold_qty' => (int) $row->sold_qty,
            ])
            ->values()
            ->all();

        DssSalesAnalytics::updateOrCreate(
            [
                'distributor_id' => $distributorId,
                'analysis_date' => $periodStart->toDateString(),
                'period_type' => 'monthly',
            ],
            [
                'product_id' => null,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_quantity_sold' => collect($topProducts)->sum('sold_qty'),
                'average_order_value' => round($averageOrderValue, 2),
                'top_products' => $topProducts,
            ]
        );
    }
}

