<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ConversationMessage;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Services\DashboardAnalyticsService;
use App\Services\DssAlertService;
use App\Services\DssEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardAnalyticsService $analytics
    ) {}

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

        $canViewFinancials = $user->role !== 'staff';

        $request->validate([
            'earnings_preset' => 'nullable|in:this_month,last_month,this_year,custom',
            'earnings_from' => 'nullable|date',
            'earnings_to' => 'nullable|date',
        ]);

        $earningsPreset = $request->input('earnings_preset', 'this_month');
        $earnings = $this->resolveEarnings($distributor->id, $canViewFinancials, $earningsPreset, $request);

        $dssService = new DssAlertService;
        $alerts = $dssService->getAlertsForDistributor($distributor->id);
        $engine = new DssEngineService;
        $engine->syncForDistributor($distributor->id);
        $dssInsights = $engine->getInsights($distributor->id);

        $expiryAlerts = $alerts['expiry_alerts']->values()->all();
        $lowStockAlerts = $alerts['low_stock_alerts']->values()->all();
        $lowStockProductIds = collect($lowStockAlerts)->pluck('product_id')->filter()->unique()->all();

        $pendingOrders = Order::where('distributor_id', $distributor->id)->where('status', 'pending')->count();
        $processingOrders = Order::where('distributor_id', $distributor->id)
            ->whereIn('status', ['approved', 'packed', 'shipped'])->count();
        $ordersMtd = (int) Order::where('distributor_id', $distributor->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfDay()])->count();
        $totalProducts = Product::where('distributor_id', $distributor->id)->count();

        $rxBacklog = (int) Order::where('distributor_id', $distributor->id)
            ->where('prescription_status', Order::PRESCRIPTION_PENDING_REVIEW)->count();

        $unreadMessages = (int) ConversationMessage::query()
            ->whereHas('conversation', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->where('user_id', '!=', $distributor->user_id)
            ->whereNull('read_at')
            ->count();

        $lowStockCount = Inventory::whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->whereRaw('quantity <= reorder_level')
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('product_variation_id')
                        ->whereRaw('NOT EXISTS (SELECT 1 FROM product_variations WHERE product_variations.product_id = inventory.product_id AND product_variations.is_active = 1)');
                })->orWhere(function ($sub) {
                    $sub->whereNotNull('product_variation_id')
                        ->whereRaw('EXISTS (SELECT 1 FROM product_variations WHERE product_variations.id = inventory.product_variation_id AND product_variations.is_active = 1)');
                });
            })
            ->count();

        $recentOrders = Order::with(['customer', 'items.product.category'])
            ->where('distributor_id', $distributor->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => $this->analytics->formatOrderForDashboard($order, $lowStockProductIds))
            ->sort(function (array $a, array $b) {
                if ($a['priority_score'] !== $b['priority_score']) {
                    return $b['priority_score'] <=> $a['priority_score'];
                }

                return strcmp($b['created_at'], $a['created_at']);
            })
            ->values()
            ->all();

        $orderPipeline = $this->analytics->buildOrderPipeline($distributor->id);

        $invAgg = Inventory::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->selectRaw('COALESCE(SUM(quantity), 0) as qty_total, COALESCE(SUM(reserved_quantity), 0) as res_total')
            ->first();

        $recommendations = collect($dssInsights['recommendations'])->map(fn ($r) => [
            'id' => $r->id,
            'product_id' => $r->product_id,
            'product_name' => optional($r->product)->name ?? ('Product #'.$r->product_id),
            'priority' => $r->priority,
            'current_stock' => (int) $r->current_stock,
            'recommended_quantity' => (int) $r->recommended_quantity,
            'days_until_stockout' => (int) $r->days_until_stockout,
            'avg_daily_sales' => (float) $r->avg_daily_sales,
        ])->values()->all();

        $expiredInStock = (int) Inventory::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributor->id))
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->count();

        $expiryWarningDays = max(1, min(365, (int) ($dssInsights['settings']->expiry_warning_days ?? 60)));
        $expiringSoonBatches = count($expiryAlerts);
        $stockoutRiskCount = collect($recommendations)->filter(
            fn ($r) => ($r['days_until_stockout'] ?? 999) <= 5 && ($r['days_until_stockout'] ?? -1) >= 0
        )->count();

        $alertCenter = $this->analytics->buildAlertCenterBanners(
            $expiredInStock,
            $expiringSoonBatches,
            $stockoutRiskCount,
            $expiryWarningDays
        );

        $newestOrderId = (int) (Order::where('distributor_id', $distributor->id)->max('id') ?? 0);
        $dssWarning = $this->analytics->assessDssRisk($distributor->id);

        return Inertia::render('Owner/Dashboard', [
            'distributor' => $distributor,
            'canViewFinancials' => $canViewFinancials,
            'stats' => [
                'pendingOrders' => $pendingOrders,
                'processingOrders' => $processingOrders,
                'ordersMtd' => $ordersMtd,
                'totalProducts' => $totalProducts,
                'lowStockCount' => $lowStockCount,
                'rxBacklog' => $rxBacklog,
                'unreadMessages' => $unreadMessages,
            ],
            'earnings' => $earnings,
            'alert_center' => $alertCenter,
            'dssWarning' => $dssWarning,
            'recentOrders' => $recentOrders,
            'order_pipeline' => $orderPipeline,
            'inventory_pulse' => [
                'quantity_total' => (int) ($invAgg->qty_total ?? 0),
                'reserved_total' => (int) ($invAgg->res_total ?? 0),
            ],
            'restock_insights' => [
                'recommendations' => $recommendations,
            ],
            'pulse_baseline' => [
                'newest_order_id' => $newestOrderId,
            ],
        ]);
    }

    private function resolveEarnings(int $distributorId, bool $canViewFinancials, string $preset, Request $request): array
    {
        if (! $canViewFinancials) {
            return [
                'preset' => $preset,
                'amount' => 0,
                'previous_amount' => 0,
                'label' => '',
                'comparison_label' => '',
                'from' => null,
                'to' => null,
            ];
        }

        $now = now();

        switch ($preset) {
            case 'last_month':
                $from = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                $to = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                $prevFrom = $now->copy()->subMonths(2)->startOfMonth()->startOfDay();
                $prevTo = $now->copy()->subMonths(2)->endOfMonth()->endOfDay();
                $label = $from->format('F Y');
                $compLabel = 'vs '.$prevFrom->format('F Y');
                break;

            case 'this_year':
                $from = $now->copy()->startOfYear()->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subYear()->startOfYear()->startOfDay();
                $prevTo = $now->copy()->subYear()->endOfDay();
                $label = $from->format('Y');
                $compLabel = 'vs '.$prevFrom->format('Y');
                break;

            case 'custom':
                $from = $request->filled('earnings_from')
                    ? Carbon::parse($request->input('earnings_from'))->startOfDay()
                    : $now->copy()->startOfMonth()->startOfDay();
                $to = $request->filled('earnings_to')
                    ? Carbon::parse($request->input('earnings_to'))->endOfDay()
                    : $now->copy()->endOfDay();
                $span = $from->diffInDays($to);
                $prevTo = $from->copy()->subDay()->endOfDay();
                $prevFrom = $prevTo->copy()->subDays($span)->startOfDay();
                $label = $this->analytics->formatRangeLabel($from, $to);
                $compLabel = 'vs previous '.($span + 1).' days';
                break;

            default:
                $from = $now->copy()->startOfMonth()->startOfDay();
                $to = $now->copy()->endOfDay();
                $prevFrom = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                $prevTo = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                $label = $from->format('F Y');
                $compLabel = 'vs '.$prevFrom->format('F Y');
                break;
        }

        $amount = $this->analytics->sumVerifiedNetSellerForDistributor($distributorId, $from, $to);
        $previousAmount = $this->analytics->sumVerifiedNetSellerForDistributor($distributorId, $prevFrom, $prevTo);

        return [
            'preset' => $preset,
            'amount' => $amount,
            'previous_amount' => $previousAmount,
            'label' => $label,
            'comparison_label' => $compLabel,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
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
}
