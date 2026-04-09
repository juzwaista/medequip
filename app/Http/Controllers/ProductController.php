<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display product catalog with filters
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'distributor', 'images', 'inventory'])
            ->withAvg(['reviews' => fn ($q) => $q->where('is_hidden', false)], 'stars')
            ->withCount(['reviews' => fn ($q) => $q->where('is_hidden', false)])
            ->withSum(['orderItems as units_sold' => function($q) {
                $q->whereHas('order', function($oq) {
                    $oq->whereIn('status', ['completed', 'delivered']);
                });
            }], 'quantity')
            ->whereHas('distributor', function ($q) {
                $q->where('status', '!=', 'banned');
            })
            ->where('is_active', true);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter: selected category and all nested descendants
        if ($request->filled('category')) {
            $catId = (int) $request->category;
            $query->whereIn('category_id', Category::descendantIdsIncludingSelf($catId));
        }

        // Distributor filter
        if ($request->filled('distributor')) {
            $query->where('distributor_id', $request->distributor);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Product type filter (equipment/consumable)
        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        // Sorting
        $sort = $request->get('sort', 'popularity');
        switch ($sort) {
            case 'popularity':
                $query->orderByPopularity();
                break;
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate(24)->withQueryString();

        // Get categories and distributors for filters
        $categories = Category::whereNull('parent_id')->with('children')->get();
        $distributors = \App\Models\Distributor::where('is_verified', true)
            ->select('id', 'company_name')
            ->orderBy('company_name')
            ->get();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'distributors' => $distributors,
            'filters' => $request->only(['search', 'category', 'distributor', 'min_price', 'max_price', 'type', 'sort']),
        ]);
    }

    /**
     * Display product detail page
     */
    public function show(Request $request, $id)
    {
        $product = Product::with([
            'category',
            'distributor.branches',
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'inventory.branch',
            'variations' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ])
            ->whereHas('distributor', function ($q) {
                $q->where('status', '!=', 'banned');
            })
            ->where('is_active', true)
            ->findOrFail($id);

        // DSS Engine: Frequently Bought Together
        $fbtProductIds = \Illuminate\Support\Facades\DB::table('order_items')
            ->whereIn('order_id', function ($query) use ($product) {
                $query->select('order_id')
                    ->from('order_items')
                    ->where('product_id', $product->id);
            })
            ->where('product_id', '!=', $product->id)
            ->select('product_id')
            ->selectRaw('COUNT(*) as frequency')
            ->groupBy('product_id')
            ->orderByDesc('frequency')
            ->limit(4)
            ->pluck('product_id')
            ->toArray();

        $relatedProducts = collect();
        if (! empty($fbtProductIds)) {
            $relatedProducts = Product::with(['images', 'distributor'])
                ->whereIn('id', $fbtProductIds)
                ->where('is_active', true)
                ->get()
                ->sortBy(function ($model) use ($fbtProductIds) {
                    return array_search($model->id, $fbtProductIds);
                })
                ->values();
        }

        $relatedProducts->each(function ($item) {
            $item->is_dss_recommendation = true;
        });

        // Fallback: Pad with category items if we lack correlation data
        if ($relatedProducts->count() < 4) {
            $excludeIds = $relatedProducts->pluck('id')->push($product->id)->toArray();

            $fallbackProducts = Product::with(['images', 'distributor'])
                ->where('category_id', $product->category_id)
                ->whereNotIn('id', $excludeIds)
                ->where('is_active', true)
                ->limit(4 - $relatedProducts->count())
                ->get();

            $fallbackProducts->each(function ($item) {
                $item->is_dss_recommendation = false;
            });

            $relatedProducts = $relatedProducts->concat($fallbackProducts);
        }

        $variationStocks = $product->variations->where('is_active', true)->values()->map(function ($v) use ($product) {
            $available = (int) $product->inventory->where('product_variation_id', $v->id)->sum(function ($inv) {
                return $inv->quantity - $inv->reserved_quantity;
            });

            return [
                'id' => $v->id,
                'option_name' => $v->option_name,
                'option_value' => $v->option_value,
                'combination' => $v->combination,
                'display_label' => $v->display_label,
                'price_adjustment' => (float) $v->price_adjustment,
                'available' => $available,
            ];
        })->values();

        $hasVariations = $variationStocks->isNotEmpty();

        $variationGroups = is_array($product->variation_options) && ! empty($product->variation_options)
            ? $product->variation_options
            : [];

        if ($hasVariations) {
            $totalStock = $product->aggregateStockQuantity();
            $availableStock = (int) $variationStocks->sum('available');
        } else {
            $totals = $product->stockTotals();
            $totalStock = $totals['quantity'];
            $availableStock = max(0, $totals['quantity'] - $totals['reserved']);
        }
        $nearestExpiryInventory = $product->inventory
            ->filter(fn ($inv) => ! is_null($inv->expiry_date))
            ->sortBy('expiry_date')
            ->first();

        $messagingUrl = $this->shopMessagingStartUrlFor($request->user(), $product->distributor, (int) $product->id);
        $hideSellerMessageCta = $this->isOwnShopProduct($request->user(), $product);

        $reviewAgg = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_hidden', false)
            ->selectRaw('AVG(stars) as avg_stars, COUNT(*) as review_count')
            ->first();

        $productReviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_hidden', false)
            ->with(['user:id,name'])
            ->orderByDesc('created_at')
            ->limit(40)
            ->get()
            ->map(fn (ProductReview $r) => [
                'id' => $r->id,
                'stars' => (int) $r->stars,
                'body' => $r->body,
                'created_at' => $r->created_at->toIso8601String(),
                'reviewer_name' => $r->user?->name ?? 'Customer',
            ])
            ->values()
            ->all();

        return Inertia::render('Products/Show', [
            'product' => $product,
            'product_review_summary' => [
                'avg' => $reviewAgg && (int) $reviewAgg->review_count > 0
                    ? round((float) $reviewAgg->avg_stars, 1)
                    : null,
                'count' => (int) ($reviewAgg->review_count ?? 0),
            ],
            'product_reviews' => $productReviews,
            'relatedProducts' => $relatedProducts,
            'totalStock' => $totalStock,
            'availableStock' => $availableStock,
            'hasVariations' => $hasVariations,
            'variationGroups' => $variationGroups,
            'variationStocks' => $variationStocks,
            'nearestExpiryDate' => $nearestExpiryInventory?->expiry_date?->format('Y-m-d'),
            'nearestBatchNumber' => $nearestExpiryInventory?->batch_number,
            'messaging' => $messagingUrl ? ['start_url' => $messagingUrl] : null,
            'hide_seller_message_cta' => $hideSellerMessageCta,
        ]);
    }

    /**
     * Search products (for autocomplete/search bar)
     */
    public function search(Request $request)
    {
        $query = $request->get('q');

        if (empty($query)) {
            return response()->json(['products' => [], 'distributors' => []]);
        }

        $products = Product::with(['images', 'distributor'])
            ->whereHas('distributor', function ($q) {
                $q->where('status', '!=', 'banned');
            })
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get();

        $distributors = \App\Models\Distributor::whereIn('status', ['approved', 'active'])
            ->where(function ($q) use ($query) {
                $q->where('company_name', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($uq) use ($query) {
                        $uq->where('name', 'like', "%{$query}%");
                    });
            })
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->limit(3)
            ->get();

        return response()->json([
            'products' => $products,
            'distributors' => $distributors,
        ]);
    }

    /**
     * Get products by category
     */
    public function byCategory(Category $category, Request $request)
    {
        $categoryIds = Category::descendantIdsIncludingSelf((int) $category->id);

        $query = Product::with(['images', 'distributor', 'inventory'])
            ->whereHas('distributor', function ($q) {
                $q->where('status', '!=', 'banned');
            })
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds);

        // Apply same filters and sorting as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(24)->withQueryString();

        return Inertia::render('Products/Category', [
            'category' => $category->load('children'),
            'products' => $products,
            'filters' => $request->only(['search', 'sort']),
        ]);
    }
}
