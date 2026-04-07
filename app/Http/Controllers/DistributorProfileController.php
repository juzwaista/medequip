<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DistributorProfileController extends Controller
{
    public function show(Request $request, $slug)
    {
        $slugKey = Str::lower(trim((string) $slug));
        $distributor = Distributor::with('owner')
            ->whereRaw('LOWER(slug) = ?', [$slugKey])
            ->firstOrFail();

        $distributorId = $distributor->id;
        $tab = strtolower(trim((string) $request->query('tab', 'shop')));

        if (! in_array($tab, ['shop', 'products', 'categories', 'reviews'], true)) {
            $tab = 'shop';
        }

        $shopReviewAgg = ProductReview::query()
            ->whereIn('product_id', Product::where('distributor_id', $distributorId)->select('id'))
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as review_count')
            ->first();

        $stats = [
            'total_products' => Product::where('distributor_id', $distributorId)->where('is_active', true)->count(),
            'active_since' => $distributor->created_at->format('Y'),
            'shop_rating_avg' => $shopReviewAgg && (int) $shopReviewAgg->review_count > 0
                ? round((float) $shopReviewAgg->avg_stars, 1)
                : null,
            'shop_rating_count' => (int) ($shopReviewAgg->review_count ?? 0),
        ];

        $shopCategories = $this->buildShopCategories($distributorId);
        $messagingUrl = $this->shopMessagingStartUrlFor($request->user(), $distributor, null);

        $payload = [
            'distributor' => $distributor,
            'stats' => $stats,
            'shopCategories' => $shopCategories,
            'activeTab' => $tab,
            'messaging' => $messagingUrl ? ['start_url' => $messagingUrl] : null,
        ];

        match ($tab) {
            'shop' => $payload = array_merge($payload, $this->shopTabData($distributorId)),
            'products' => $payload = array_merge($payload, $this->productsTabData($request, $distributorId)),
            'reviews' => $payload = array_merge($payload, $this->reviewsTabData($request, $distributorId)),
            default => null,
        };

        return Inertia::render('Seller/Profile', $payload);
    }

    private function shopTabData(int $distributorId): array
    {
        $baseQuery = fn () => Product::where('distributor_id', $distributorId)
            ->where('is_active', true)
            ->with(['images', 'category']);

        $featured = $baseQuery()
            ->where('is_featured', true)
            ->withAvg('reviews', 'stars')
            ->withCount('reviews')
            ->limit(8)
            ->get();

        if ($featured->isNotEmpty()) {
            $recommended = $featured;
        } else {
            $recommended = $baseQuery()
                ->withAvg('reviews', 'stars')
                ->withCount('reviews')
                ->having('reviews_count', '>', 0)
                ->orderByDesc('reviews_avg_stars')
                ->limit(8)
                ->get();

            if ($recommended->isEmpty()) {
                $recommended = $baseQuery()
                    ->withAvg('reviews', 'stars')
                    ->withCount('reviews')
                    ->latest()
                    ->limit(8)
                    ->get();
            }
        }

        $topSellerIds = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.distributor_id', $distributorId)
            ->whereIn('orders.status', ['completed', 'delivered'])
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as units_sold'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('units_sold')
            ->limit(8)
            ->pluck('order_items.product_id')
            ->toArray();

        if (! empty($topSellerIds)) {
            $topSellers = $baseQuery()
                ->withAvg('reviews', 'stars')
                ->withCount('reviews')
                ->whereIn('id', $topSellerIds)
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $topSellerIds))
                ->values();
        } else {
            $topSellers = $baseQuery()
                ->withAvg('reviews', 'stars')
                ->withCount('reviews')
                ->latest()
                ->limit(8)
                ->get();
        }

        return [
            'recommendedProducts' => $recommended,
            'topSellers' => $topSellers,
        ];
    }

    private function productsTabData(Request $request, int $distributorId): array
    {
        $query = Product::where('distributor_id', $distributorId)
            ->where('is_active', true)
            ->with(['images', 'category', 'inventory'])
            ->withAvg('reviews', 'stars')
            ->withCount('reviews');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $catId = (int) $request->category;
            $childIds = Category::where('parent_id', $catId)->pluck('id')->toArray();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }

        $sort = $request->get('sort', 'newest');
        match ($sort) {
            'price_low' => $query->orderBy('base_price', 'asc'),
            'price_high' => $query->orderBy('base_price', 'desc'),
            'best_rated' => $query->orderByDesc('reviews_avg_stars'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return [
            'products' => $query->paginate(16)->withQueryString(),
            'filters' => $request->only(['search', 'category', 'sort']),
        ];
    }

    private function reviewsTabData(Request $request, int $distributorId): array
    {
        $reviews = ProductReview::query()
            ->whereHas('product', fn ($q) => $q->where('distributor_id', $distributorId))
            ->with(['product:id,name,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->paginate(16)
            ->withQueryString();

        return [
            'shopReviews' => $reviews,
        ];
    }

    private function buildShopCategories(int $distributorId): array
    {
        $inStockConstraint = function ($q) use ($distributorId) {
            $q->where('distributor_id', $distributorId)
                ->where('is_active', true);
        };

        $categories = Category::whereNull('parent_id')
            ->withCount(['products as shop_product_count' => $inStockConstraint])
            ->with(['children' => function ($q) use ($inStockConstraint) {
                $q->withCount(['products as shop_product_count' => $inStockConstraint])
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return $categories
            ->map(function ($cat) {
                $childTotal = $cat->children->sum('shop_product_count');
                $total = $cat->shop_product_count + $childTotal;

                if ($total === 0) {
                    return null;
                }

                $cat->total_product_count = $total;
                $cat->children = $cat->children->filter(fn ($c) => $c->shop_product_count > 0)->values();

                return $cat;
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
