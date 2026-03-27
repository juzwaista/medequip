<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Services\ProductCatalogSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display unified inventory management (products + stock)
     */
    public function index(Request $request)
    {
        $distributor = $this->getDistributor();

        Log::info('[InventoryController] Inventory page accessed', [
            'user_id' => auth()->id(),
            'has_distributor' => !is_null($distributor)
        ]);

        if (!$distributor) {
            return redirect('/owner/dashboard')
                ->withErrors(['error' => 'Please create a distributor profile first.']);
        }

        // Get products WITH their inventory totals
        $query = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'inventory', 'variations']);

        // ---------------------------------------------------------
        // NEW: DASHBOARD ALERT FILTERS
        // ---------------------------------------------------------
        $alertFilter = $request->input('filter');
        
        if (in_array($alertFilter, ['expired'], true)) {
            $query->whereHas('inventory', function($q) {
                $q->where('quantity', '>', 0)
                  ->whereNotNull('expiry_date')
                  ->whereDate('expiry_date', '<', now()->toDateString());
            });
        } elseif (in_array($alertFilter, ['expiring', 'near_expiry', 'near-expiry', 'near_expiry_products'], true)) {
            $query->whereHas('inventory', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>', now()->toDateString())
                    ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString());
            });
        } elseif (in_array($alertFilter, ['low_stock', 'low_stock_products'], true)) {
            $query->whereHas('inventory', function($q) {
                $q->whereRaw('quantity <= reorder_level')
                  ->where(function ($subQ) {
                      $subQ->where(function ($sub) {
                          $sub->whereNull('product_variation_id')
                              ->whereRaw('NOT EXISTS (SELECT 1 FROM product_variations WHERE product_variations.product_id = inventory.product_id AND product_variations.is_active = 1)');
                      })->orWhere(function ($sub) {
                          $sub->whereNotNull('product_variation_id')
                              ->whereRaw('EXISTS (SELECT 1 FROM product_variations WHERE product_variations.id = inventory.product_variation_id AND product_variations.is_active = 1)');
                      });
                  });
            });
        } elseif ($alertFilter === 'predicted_stockout') {
            $engine = new \App\Services\DssEngineService;
            $insights = $engine->getInsights($distributor->id);
            $stockoutProductIds = collect($insights['recommendations'])
                ->filter(fn ($r) => ((int)($r->days_until_stockout ?? 999)) <= 5 && ((int)($r->days_until_stockout ?? -1)) >= 0)
                ->pluck('product_id')->toArray();
            $query->whereIn('id', $stockoutProductIds);
        }
        // ---------------------------------------------------------

        // Filter by search (name or SKU)
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by category
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by stock status — use a subquery approach to avoid invalid havingRaw inside whereHas
        if ($request->stock_status) {
            $query->whereIn('id', function ($sub) use ($request) {
                $sub->select('product_id')
                    ->from('inventory')
                    ->groupBy('product_id');

                if ($request->stock_status === 'out') {
                    $sub->havingRaw('SUM(quantity) = 0');
                } elseif ($request->stock_status === 'low') {
                    $sub->havingRaw('SUM(quantity) > 0 AND SUM(quantity) < 10');
                } elseif ($request->stock_status === 'in_stock') {
                    $sub->havingRaw('SUM(quantity) >= 10');
                }
            });
        }

        // Added ->withQueryString() so filters aren't lost on Page 2
        $products = $query->latest()->paginate(12)->withQueryString(); 

        // Add computed stock data
        $products->getCollection()->transform(function ($product) {
            $totals = $product->stockTotals();
            $totalStock = $totals['quantity'];
            $totalReserved = $totals['reserved'];
            $available = $totalStock - $totalReserved;

            $product->total_stock = $totalStock;
            $product->total_reserved = $totalReserved;
            $product->available_stock = $available;
            
            // Stock status aligned to dashboard alert logic (reorder_level),
            // so when DSS says "Low Stock Products" the UI badge matches.
            $activeCountForCheck = $product->variations->where('is_active', true)->count();
            $hasLowByReorder = (int) ($product->inventory->filter(function ($inv) use ($activeCountForCheck, $product) {
                if ($inv->product_variation_id === null) {
                    return $activeCountForCheck === 0 && (int) $inv->quantity <= (int) $inv->reorder_level;
                } else {
                    $variation = $product->variations->firstWhere('id', $inv->product_variation_id);
                    return $variation && $variation->is_active && (int) $inv->quantity <= (int) $inv->reorder_level;
                }
            })->count()) > 0;

            $isExpired = $product->inventory->filter(fn($inv) => $inv->quantity > 0 && $inv->expiry_date && \Carbon\Carbon::parse($inv->expiry_date)->startOfDay()->isPast())->isNotEmpty();
            $isNearExpiry = !$isExpired && $product->inventory->filter(fn($inv) => $inv->quantity > 0 && $inv->expiry_date && \Carbon\Carbon::parse($inv->expiry_date)->startOfDay()->diffInDays(now()->startOfDay()) <= 30 && \Carbon\Carbon::parse($inv->expiry_date)->startOfDay()->isFuture())->isNotEmpty();

            if ($totalStock === 0) {
                $product->stock_status = 'out';
                $product->stock_color = 'red';
                $product->stock_label = 'Out of Stock';
            } elseif ($isExpired) {
                $product->stock_status = 'expired';
                $product->stock_color = 'red';
                $product->stock_label = 'Expired';
            } elseif ($isNearExpiry) {
                $product->stock_status = 'near_expiry';
                $product->stock_color = 'orange';
                $product->stock_label = 'Near Expiry';
            } elseif ($hasLowByReorder) {
                $product->stock_status = 'low';
                $product->stock_color = 'orange';
                $product->stock_label = 'Low Stock';
            } elseif ($totalStock < 20) {
                $product->stock_status = 'medium';
                $product->stock_color = 'yellow';
                $product->stock_label = 'Medium Stock';
            } else {
                $product->stock_status = 'good';
                $product->stock_color = 'green';
                $product->stock_label = 'In Stock';
            }

            return $product;
        });

        // Get categories for filter
        $categories = Category::whereNull('parent_id')->select('id', 'name')->get();

        return Inertia::render('Owner/Inventory/Index', [
            'products' => $products,
            'categories' => $categories,
            // Added 'filter' to preserve the alert state
            'filters' => $request->only(['search', 'category_id', 'stock_status', 'filter']),
        ]);
    }

    /**
     * Show form to create product with initial stock
     */
    public function create()
    {
        $distributor = $this->getDistributor();

        if (!$distributor) {
            return redirect('/owner/dashboard');
        }

        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return Inertia::render('Owner/Inventory/Create', [
            'categories' => $categories,
            'medicine_category_ids' => Category::medicineTreeIds(),
        ]);
    }

    /**
     * Store product: gallery, variations, stock (per variation or base), identifiers.
     */
    public function store(Request $request, ProductCatalogSyncService $catalog)
    {
        $user = auth()->user();
        $distributor = $user->distributor;

        if (! $distributor) {
            return redirect('/owner/dashboard')
                ->withErrors(['error' => 'Please create a distributor profile first.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'requires_prescription' => 'boolean',
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
            'has_warranty' => 'boolean',
            'warranty_months' => 'nullable|integer|min:1|max:120|required_if:has_warranty,1',
            'has_expiry' => 'boolean',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'images' => 'required|array|min:1|max:12',
            'images.*' => 'image|max:8192',
            'variations_json' => 'nullable|string',
            'variation_stocks_json' => 'nullable|string',
            'primary_image_index' => 'nullable|integer|min:0',
            'initial_quantity' => 'nullable|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
            'batch_number' => 'nullable|string|max:100',
        ]);

        $variations = json_decode($request->input('variations_json', '[]'), true);
        if (! is_array($variations)) {
            $variations = [];
        }

        $varValidator = Validator::make(
            ['variations' => $variations],
            [
                'variations' => 'nullable|array',
                'variations.*.option_name' => 'required|string|max:100',
                'variations.*.option_value' => 'required|string|max:100',
                'variations.*.price_adjustment' => 'nullable|numeric',
                'variations.*.sku' => 'nullable|string|max:100',
            ]
        );

        if ($varValidator->fails()) {
            return back()->withErrors($varValidator)->withInput();
        }

        $variationStocks = json_decode($request->input('variation_stocks_json', '[]'), true);
        if (! is_array($variationStocks)) {
            $variationStocks = [];
        }

        if ($variations !== [] && count($variationStocks) !== count($variations)) {
            return back()->withErrors(['variation_stocks_json' => 'Provide initial stock for each variation row.'])->withInput();
        }

        if ($variations === [] && ! isset($validated['initial_quantity'])) {
            $validated['initial_quantity'] = 0;
        }

        $requiresRx = Category::normalizeRequiresPrescription(
            (int) $validated['category_id'],
            (bool) ($validated['requires_prescription'] ?? false)
        );

        $catalogWarnings = [];

        try {
            DB::transaction(function () use ($request, $catalog, $validated, $distributor, $variations, $variationStocks, $requiresRx, &$catalogWarnings) {
                $product = Product::create([
                    'distributor_id' => $distributor->id,
                    'name' => $validated['name'],
                    'sku' => ! empty($validated['sku'])
                        ? $validated['sku']
                        : ('PRD-' . strtoupper(Str::random(8))),
                    'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                    'description' => $validated['description'],
                    'category_id' => $validated['category_id'],
                    'brand' => $validated['brand'],
                    'model' => $validated['model'],
                    'requires_prescription' => $requiresRx,
                    'base_price' => $validated['base_price'],
                    'wholesale_price' => $validated['wholesale_price'] ?? null,
                    'wholesale_min_qty' => $validated['wholesale_min_qty'] ?? null,
                    'has_warranty' => (bool) ($validated['has_warranty'] ?? false),
                    'warranty_months' => ($validated['has_warranty'] ?? false)
                        ? ($validated['warranty_months'] ?? null)
                        : null,
                    'has_expiry' => (bool) ($validated['has_expiry'] ?? false),
                    'barcode' => $validated['barcode'] ?? null,
                    'image_path' => null,
                    'is_active' => true,
                ]);

                $files = $request->file('images', []);
                $catalog->syncImagesFromUploads($product, $files);

                if ($request->filled('primary_image_index')) {
                    $imgs = $product->images()->orderBy('sort_order')->get();
                    $idx = (int) $request->input('primary_image_index');
                    if (isset($imgs[$idx])) {
                        $catalog->setPrimaryImage($product->fresh(['images']), $imgs[$idx]->id);
                    }
                }

                if (! empty($variations)) {
                    $catalog->syncVariations($product, $variations);
                    $product->refresh();
                    $catalogWarnings = array_merge(
                        $catalogWarnings,
                        $catalog->syncVariationInventory($product, $variationStocks, (int) $validated['reorder_level'])
                    );
                } else {
                    $catalogWarnings = array_merge(
                        $catalogWarnings,
                        $catalog->setBaseInventory($product, (int) ($validated['initial_quantity'] ?? 0), (int) $validated['reorder_level'])
                    );
                }

                $firstInv = $product->inventory()->orderBy('id')->first();
                if ($firstInv) {
                    $firstInv->update([
                        'expiry_date' => ($validated['has_expiry'] ?? false) ? ($validated['expiry_date'] ?? null) : null,
                        'batch_number' => $validated['batch_number'] ?? null,
                    ]);
                }
            });

            $redirect = redirect()->route('owner.inventory.index')
                ->with('success', 'Product added to inventory successfully.');
            if ($catalogWarnings !== []) {
                $redirect->with('warning', implode(' ', $catalogWarnings));
            }

            return $redirect;
        } catch (\Throwable $e) {
            Log::error('[InventoryController] Failed to create product', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show form to edit product details (looked up by product ID)
     */
    public function edit($id)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'inventory', 'images', 'variations'])
            ->findOrFail($id);

        $variationStocks = [];
        $variationReserved = [];
        foreach ($product->variations->sortBy('sort_order') as $v) {
            $variationStocks[$v->id] = (int) $product->inventory->where('product_variation_id', $v->id)->sum('quantity');
            $variationReserved[$v->id] = (int) $product->inventory->where('product_variation_id', $v->id)->sum('reserved_quantity');
        }

        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        $baseInventory = $product->inventory->firstWhere('product_variation_id', null);

        return Inertia::render('Owner/Inventory/Edit', [
            'product' => $product,
            'inventory' => $baseInventory ?? $product->inventory->first(),
            'variation_stocks' => $variationStocks,
            'variation_reserved' => $variationReserved,
            'base_reserved_quantity' => (int) $product->inventory->whereNull('product_variation_id')->sum('reserved_quantity'),
            'categories' => $categories,
            'medicine_category_ids' => Category::medicineTreeIds(),
        ]);
    }

    /**
     * Update product details (same capabilities as create: gallery, primary image, variations, stock).
     */
    public function update(Request $request, $id, ProductCatalogSyncService $catalog)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with(['images', 'variations'])
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $id,
            'requires_prescription' => 'boolean',
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
            'has_warranty' => 'boolean',
            'warranty_months' => 'nullable|integer|min:1|max:120|required_if:has_warranty,1',
            'has_expiry' => 'boolean',
            'barcode' => 'nullable|string|max:100|unique:products,barcode,' . $id,
            'is_active' => 'boolean',
            'images' => 'nullable|array|max:12',
            'images.*' => 'image|max:8192',
            'removed_image_ids' => 'nullable|array',
            'removed_image_ids.*' => 'integer|exists:product_images,id',
            'variations_json' => 'nullable|string',
            'variation_stocks_json' => 'nullable|string',
            'primary_image_id' => 'nullable|integer|exists:product_images,id',
            'primary_image_index' => 'nullable|integer|min:0',
            'initial_quantity' => 'nullable|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|required_if:has_expiry,1',
            'batch_number' => 'nullable|string|max:100',
        ]);

        $variations = json_decode($request->input('variations_json', '[]'), true);
        if (! is_array($variations)) {
            $variations = [];
        }

        $varValidator = Validator::make(
            ['variations' => $variations],
            [
                'variations' => 'nullable|array',
                'variations.*.id' => 'nullable|integer|exists:product_variations,id',
                'variations.*.option_name' => 'required|string|max:100',
                'variations.*.option_value' => 'required|string|max:100',
                'variations.*.price_adjustment' => 'nullable|numeric',
                'variations.*.sku' => 'nullable|string|max:100',
                'variations.*.is_active' => 'nullable|boolean',
            ]
        );

        if ($varValidator->fails()) {
            return back()->withErrors($varValidator)->withInput();
        }

        $variationStocks = json_decode($request->input('variation_stocks_json', '[]'), true);
        if (! is_array($variationStocks)) {
            $variationStocks = [];
        }

        if ($variations !== [] && count($variationStocks) !== count($variations)) {
            return back()->withErrors(['variation_stocks_json' => 'Provide stock for each variation row.'])->withInput();
        }

        if ($request->filled('primary_image_id')) {
            $ok = ProductImage::where('product_id', $product->id)
                ->where('id', (int) $request->input('primary_image_id'))
                ->exists();
            if (! $ok) {
                return back()->withErrors(['primary_image_id' => 'Invalid image for this product.'])->withInput();
            }
        }

        $requiresRx = Category::normalizeRequiresPrescription(
            (int) $validated['category_id'],
            (bool) ($validated['requires_prescription'] ?? false)
        );

        $catalogWarnings = [];

        try {
            DB::transaction(function () use ($request, $catalog, $validated, $product, $variations, $variationStocks, $requiresRx, &$catalogWarnings) {
                $product->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'category_id' => $validated['category_id'],
                    'brand' => $validated['brand'],
                    'model' => $validated['model'],
                    'sku' => $validated['sku'] ?? $product->sku,
                    'requires_prescription' => $requiresRx,
                    'base_price' => $validated['base_price'],
                    'wholesale_price' => $validated['wholesale_price'] ?? null,
                    'wholesale_min_qty' => $validated['wholesale_min_qty'] ?? null,
                    'has_warranty' => (bool) ($validated['has_warranty'] ?? false),
                    'warranty_months' => ($validated['has_warranty'] ?? false)
                        ? ($validated['warranty_months'] ?? null)
                        : null,
                    'has_expiry' => (bool) ($validated['has_expiry'] ?? false),
                    'barcode' => $validated['barcode'] ?? null,
                    'is_active' => $validated['is_active'] ?? $product->is_active,
                ]);

                if (! empty($validated['removed_image_ids'])) {
                    $catalog->removeGalleryImages($product, $validated['removed_image_ids']);
                }

                if ($request->hasFile('images')) {
                    $files = array_values(array_filter(
                        $request->file('images', []),
                        fn ($f) => $f instanceof \Illuminate\Http\UploadedFile
                    ));
                    $catalog->syncImagesFromUploads($product->fresh(['images']), $files);

                    if ($request->filled('primary_image_index') && $files !== []) {
                        $n = count($files);
                        $all = $product->fresh(['images'])->images()->orderBy('sort_order')->get();
                        $lastBatch = $all->slice(-$n)->values();
                        $idx = (int) $request->input('primary_image_index');
                        if ($lastBatch->has($idx)) {
                            $catalog->setPrimaryImage($product->fresh(['images']), (int) $lastBatch[$idx]->id);
                        }
                    }
                }

                if ($request->filled('primary_image_id')) {
                    $catalog->setPrimaryImage($product->fresh(['images']), (int) $request->input('primary_image_id'));
                }

                $product->refresh();
                if ($product->images()->count() === 0) {
                    throw new \RuntimeException('Add at least one product image.');
                }

                $catalog->syncVariations($product, $variations);
                $product->refresh();

                if (! empty($variations)) {
                    $catalogWarnings = array_merge(
                        $catalogWarnings,
                        $catalog->syncVariationInventory($product, $variationStocks, (int) $validated['reorder_level'])
                    );
                } else {
                    $catalogWarnings = array_merge(
                        $catalogWarnings,
                        $catalog->setBaseInventory($product, (int) ($validated['initial_quantity'] ?? 0), (int) $validated['reorder_level'])
                    );
                }

                $firstInv = $product->inventory()->orderBy('id')->first();
                if ($firstInv) {
                    $firstInv->update([
                        'expiry_date' => ($validated['has_expiry'] ?? false) ? ($validated['expiry_date'] ?? null) : null,
                        'batch_number' => $validated['batch_number'] ?? null,
                    ]);
                }
            });

            $redirect = redirect()->route('owner.inventory.index')
                ->with('success', 'Product updated successfully.');
            if ($catalogWarnings !== []) {
                $redirect->with('warning', implode(' ', $catalogWarnings));
            }

            return $redirect;
        } catch (\Throwable $e) {
            Log::error('[InventoryController] Product update failed', ['error' => $e->getMessage()]);

            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Quick stock adjustment
     */
    public function adjustStock(Request $request, $id)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with('inventory')
            ->findOrFail($id);

        $hasVariations = $product->variations()->where('is_active', true)->exists();

        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'reason' => 'nullable|string|max:255',
            'product_variation_id' => $hasVariations ? 'required|exists:product_variations,id' : 'nullable|exists:product_variations,id',
        ]);

        if ($hasVariations) {
            $variation = $product->variations()->where('id', $validated['product_variation_id'])->first();
            if (! $variation) {
                return back()->withErrors(['error' => 'Invalid product option.']);
            }
        }

        $variationId = $hasVariations ? (int) $validated['product_variation_id'] : null;

        $inventory = $variationId
            ? $product->inventory->firstWhere('product_variation_id', $variationId)
            : $product->inventory->first(fn ($inv) => $inv->product_variation_id === null);

        if (! $inventory) {
            $inventory = Inventory::create([
                'product_id' => $product->id,
                'product_variation_id' => $variationId,
                'branch_id' => null,
                'quantity' => 0,
                'reorder_level' => 10,
                'reserved_quantity' => 0,
            ]);
        }

        $newQuantity = $inventory->quantity + $validated['adjustment'];

        if ($newQuantity < 0) {
            return back()->withErrors(['error' => 'Stock cannot be negative.']);
        }

        $inventory->update(['quantity' => $newQuantity]);

        Log::info('[InventoryController] Stock adjusted', [
            'product_id' => $product->id,
            'adjustment' => $validated['adjustment'],
            'new_quantity' => $newQuantity,
            'reason' => $validated['reason'] ?? 'not specified'
        ]);

        return back()->with('success', "Stock adjusted successfully. New quantity: {$newQuantity}");
    }

    /**
     * Archive product (soft delete only — no hard deletion of DB rows or files).
     */
    public function destroy($id)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with('inventory')
            ->findOrFail($id);

        $hasReserved = $product->inventory->where('reserved_quantity', '>', 0)->count() > 0;

        if ($hasReserved) {
            return back()->withErrors(['error' => 'Cannot archive a product while stock is reserved on pending orders.']);
        }

        $product->update(['is_active' => false]);
        $product->delete();

        Log::info('[InventoryController] Product archived (soft deleted)', [
            'product_id' => $id,
        ]);

        return redirect()->route('owner.inventory.index')
            ->with('success', 'Product archived. It remains in the database and can be restored by an administrator if needed.');
    }
}