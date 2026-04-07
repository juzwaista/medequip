<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use App\Rules\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display products for this distributor
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->employer : $user->distributor;

        if (! $distributor) {
            if ($user->role === 'staff') {
                abort(403, 'Your staff account is not assigned to a distributor. Please contact your employer.');
            }

            return redirect()->route('owner.distributors.create')
                ->with('error', 'Please create a distributor profile first.');
        }

        $query = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'inventory']);

        if ($request->search) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->paginate(12);

        // Add total stock
        $products->getCollection()->transform(function ($product) {
            $product->total_stock = $product->aggregateStockQuantity();

            return $product;
        });

        return Inertia::render('Owner/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show create product form
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return Inertia::render('Owner/Products/Create', [
            'categories' => $categories,
            'medicine_category_ids' => Category::medicineTreeIds(),
        ]);
    }

    /**
     * Store new product
     */
    public function store(Request $request)
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
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
            'requires_prescription' => 'boolean',
            'images' => 'required|array|min:1|max:12',
            'images.*' => ['image', 'max:4096', SafeUpload::image()],
            'variations_json' => 'nullable|string',
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

        $requiresRx = Category::normalizeRequiresPrescription(
            (int) $validated['category_id'],
            (bool) ($validated['requires_prescription'] ?? false)
        );

        try {
            $product = Product::create([
                'distributor_id' => $distributor->id,
                'name' => $validated['name'],
                'sku' => 'PRD-'.strtoupper(Str::random(8)),
                'slug' => Str::slug($validated['name']).'-'.Str::random(6),
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'brand' => $validated['brand'],
                'model' => $validated['model'],
                'base_price' => $validated['base_price'],
                'wholesale_price' => $validated['wholesale_price'] ?? null,
                'wholesale_min_qty' => $validated['wholesale_min_qty'] ?? null,
                'requires_prescription' => $requiresRx,
                'image_path' => null,
                'is_active' => true,
            ]);

            $this->syncProductImagesFromUploads($product, $request->file('images'));

            if (! empty($variations)) {
                foreach ($variations as $i => $row) {
                    ProductVariation::create([
                        'product_id' => $product->id,
                        'option_name' => $row['option_name'],
                        'option_value' => $row['option_value'],
                        'price_adjustment' => $row['price_adjustment'] ?? 0,
                        'sku' => $row['sku'] ?? null,
                        'sort_order' => $i,
                        'is_active' => true,
                    ]);
                }
            }

            return redirect()->route('owner.inventory.index')
                ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            Log::error('[Owner\\ProductController] Product creation failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: '.$e->getMessage()]);
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'images', 'variations'])
            ->findOrFail($id);

        $categories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

        return Inertia::render('Owner/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'medicine_category_ids' => Category::medicineTreeIds(),
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
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
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'requires_prescription' => 'boolean',
            'images' => 'nullable|array|max:12',
            'images.*' => ['image', 'max:4096', SafeUpload::image()],
            'removed_image_ids' => 'nullable|array',
            'removed_image_ids.*' => 'integer|exists:product_images,id',
            'variations_json' => 'nullable|string',
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

        $requiresRx = Category::normalizeRequiresPrescription(
            (int) $validated['category_id'],
            (bool) ($validated['requires_prescription'] ?? false)
        );

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'brand' => $validated['brand'],
            'model' => $validated['model'],
            'base_price' => $validated['base_price'],
            'wholesale_price' => $validated['wholesale_price'] ?? null,
            'wholesale_min_qty' => $validated['wholesale_min_qty'] ?? null,
            'is_active' => $validated['is_active'] ?? $product->is_active,
            'requires_prescription' => $requiresRx,
        ];

        $product->update($updateData);

        // Remove selected images
        if (! empty($validated['removed_image_ids'])) {
            foreach ($validated['removed_image_ids'] as $imageId) {
                $img = ProductImage::where('product_id', $product->id)->where('id', $imageId)->first();
                if ($img) {
                    Storage::disk('public')->delete($img->image_path);
                    $img->delete();
                }
            }
        }

        if ($request->hasFile('images')) {
            $startOrder = (int) $product->images()->max('sort_order');
            $files = $request->file('images');
            foreach ($files as $i => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => false,
                    'sort_order' => $startOrder + $i + 1,
                ]);
            }
        }

        $this->refreshPrimaryImagePath($product);

        if ($product->images()->count() === 0) {
            return back()->withErrors(['images' => 'Add at least one product image.'])->withInput();
        }

        // Sync variations
        $this->syncProductVariations($product, $variations);

        return redirect()->route('owner.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Delete a gallery image
     */
    public function destroyImage(Product $product, ProductImage $image)
    {
        $distributor = $this->getDistributor();

        if ($product->distributor_id !== $distributor->id || $image->product_id !== $product->id) {
            abort(403);
        }

        Storage::disk('public')->delete($image->image_path);
        $image->delete();
        $this->refreshPrimaryImagePath($product->fresh(['images']));

        return back()->with('success', 'Image removed.');
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        $distributor = $this->getDistributor();

        $product = Product::where('distributor_id', $distributor->id)
            ->with('images')
            ->findOrFail($id);

        // Check if product has active inventory
        if ($product->inventory()->where('quantity', '>', 0)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete product with existing inventory.']);
        }

        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('owner.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    private function syncProductImagesFromUploads(Product $product, array $files): void
    {
        foreach ($files as $i => $file) {
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $i === 0,
                'sort_order' => $i,
            ]);
        }

        $this->refreshPrimaryImagePath($product->fresh(['images']));
    }

    private function refreshPrimaryImagePath(Product $product): void
    {
        $images = $product->images()->orderBy('sort_order')->get();
        if ($images->isEmpty()) {
            $product->update(['image_path' => null]);

            return;
        }

        $images->each(function ($img, $idx) {
            $img->update([
                'is_primary' => $idx === 0,
                'sort_order' => $idx,
            ]);
        });

        $first = $images->first();
        $product->update(['image_path' => $first->image_path]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncProductVariations(Product $product, array $rows): void
    {
        $incomingIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $toRemove = ProductVariation::where('product_id', $product->id)
            ->whereNotIn('id', $incomingIds)
            ->get();

        /** @var ProductVariation $v */
        foreach ($toRemove as $v) {
            if ($v->inventory()->exists()) {
                throw new \RuntimeException(
                    'Cannot remove option "'.$v->option_name.': '.$v->option_value.'" because stock is still assigned to it.'
                );
            }
            $v->delete();
        }

        foreach ($rows as $i => $row) {
            $payload = [
                'option_name' => $row['option_name'],
                'option_value' => $row['option_value'],
                'price_adjustment' => $row['price_adjustment'] ?? 0,
                'sku' => $row['sku'] ?? null,
                'sort_order' => $i,
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
            ];

            if (! empty($row['id'])) {
                $existing = ProductVariation::where('product_id', $product->id)
                    ->where('id', $row['id'])
                    ->first();
                if ($existing) {
                    $existing->update($payload);

                    continue;
                }
            }

            ProductVariation::create(array_merge($payload, [
                'product_id' => $product->id,
            ]));
        }
    }
}
