<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DistributorProfileController extends Controller
{
    /**
     * Distributor account owner or staff assigned to this shop (for previewing inactive listings).
     */
    private function viewerManagesShop(Request $request, Distributor $distributor): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }
        if ((int) $distributor->user_id === (int) $user->id) {
            return true;
        }
        if ($user->role === 'staff' && (int) ($user->distributor_id ?? 0) === (int) $distributor->id) {
            return true;
        }

        return false;
    }

    /**
     * Same rules as the main catalog for guests (see ProductController).
     */
    private function applyPublicListingConstraints(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('distributor', function ($q) {
                $q->where('status', '!=', 'banned');
            });
    }

    public function show(Request $request, $slug)
    {
        $slugKey = Str::lower(trim((string) $slug));
        $distributor = Distributor::with('owner')
            ->whereRaw('LOWER(slug) = ?', [$slugKey])
            ->firstOrFail();

        /** @var Distributor $distributor */
        $distributorId = $distributor->id;
        $tab = strtolower(trim((string) $request->query('tab', 'shop')));

        if (! in_array($tab, ['shop', 'products', 'categories', 'reviews'], true)) {
            $tab = 'shop';
        }

        $shopReviewAgg = ProductReview::query()
            ->whereIn('product_id', Product::where('distributor_id', $distributorId)->select('id'))
            ->where('is_hidden', false)
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
        $isOwner = $this->viewerManagesShop($request, $distributor);

        $payload = [
            'distributor' => $distributor,
            'stats' => $stats,
            'shopCategories' => $shopCategories,
            'activeTab' => $tab,
            'messaging' => $messagingUrl ? ['start_url' => $messagingUrl] : null,
            'isOwner' => $isOwner,
        ];

        match ($tab) {
            'shop' => $payload = array_merge($payload, $this->shopTabData($distributorId, $isOwner)),
            'products' => $payload = array_merge($payload, $this->productsTabData($request, $distributorId, $isOwner)),
            'reviews' => $payload = array_merge($payload, $this->reviewsTabData($request, $distributorId)),
            default => null,
        };

        return Inertia::render('Seller/Profile', $payload);
    }

    private function shopTabData(int $distributorId, bool $isOwner = false): array
    {
        $baseQuery = function () use ($distributorId, $isOwner) {
            $q = Product::where('distributor_id', $distributorId);
            if (! $isOwner) {
                $this->applyPublicListingConstraints($q);
            }

            return $q->with(['images', 'category', 'inventory'])
                ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'stars')
                ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
                ->withSum(['orderItems as units_sold' => function ($sq) {
                    $sq->whereHas('order', fn ($oq) => $oq->whereIn('status', ['completed', 'delivered']));
                }], 'quantity');
        };

        // 1. Recommended (Featured first)
        $featured = $baseQuery()
            ->where('is_featured', true)
            ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'stars')
            ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
            ->limit(8)
            ->get();

        if ($featured->isNotEmpty()) {
            $recommended = $featured;
        } else {
            $recommended = $baseQuery()
                ->orderByPopularity()
                ->limit(8)
                ->get();
        }

        // 2. Top Sellers (Popularity first fallback)
        $topSellerQuery = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('products.distributor_id', $distributorId)
            ->whereIn('orders.status', ['completed', 'delivered']);

        if (! $isOwner) {
            $topSellerQuery
                ->where('products.is_active', true)
                ->whereNull('products.deleted_at')
                ->whereExists(function ($q) {
                    $q->selectRaw('1')
                        ->from('distributors')
                        ->whereColumn('distributors.id', 'products.distributor_id')
                        ->where('distributors.status', '!=', 'banned');
                });
        }

        $topSellerIds = $topSellerQuery
            ->select('order_items.product_id', DB::raw('SUM(order_items.quantity) as units_sold'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('units_sold')
            ->limit(8)
            ->pluck('order_items.product_id')
            ->toArray();

        if (! empty($topSellerIds)) {
            $topSellers = $baseQuery()
                ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'stars')
                ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
                ->whereIn('id', $topSellerIds)
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $topSellerIds))
                ->values();

            if ($topSellers->isEmpty()) {
                $topSellers = $baseQuery()
                    ->orderByPopularity()
                    ->limit(8)
                    ->get();
            }
        } else {
            $topSellers = $baseQuery()
                ->orderByPopularity()
                ->limit(8)
                ->get();
        }

        return [
            'recommendedProducts' => $recommended,
            'topSellers' => $topSellers,
        ];
    }

    private function productsTabData(Request $request, int $distributorId, bool $isOwner = false): array
    {
        $query = Product::where('distributor_id', $distributorId);
        if (! $isOwner) {
            $this->applyPublicListingConstraints($query);
        }
        $query->with(['images', 'category', 'inventory'])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'stars')
            ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)]);

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
            $query->whereIn('category_id', Category::descendantIdsIncludingSelf($catId));
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
            ->where('is_hidden', false)
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
                ->where('is_active', true)
                ->whereHas('distributor', function ($dq) {
                    $dq->where('status', '!=', 'banned');
                });
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
