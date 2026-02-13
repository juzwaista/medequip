<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display products for this distributor
     */
    public function index(Request $request)
    {
        $distributor = auth()->user()->distributor;

        if (!$distributor) {
            return redirect()->route('distributors.create')
                ->with('error', 'Please create a distributor profile first.');
        }

        $query = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'inventory']);

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(12);

        // Add total stock
        $products->getCollection()->transform(function ($product) {
            $product->total_stock = $product->inventory->sum('quantity');
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
        $categories = Category::whereNull('parent_id')->select('id', 'name')->get();

        return Inertia::render('Owner/Products/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->distributor;

        \Log::info('[ProductController] Product creation initiated', [
            'user_id' => $user->id,
            'has_distributor' => !is_null($distributor),
            'distributor_id' => $distributor?->id
        ]);

        if (!$distributor) {
            \Log::error('[ProductController] No distributor profile found', [
                'user_id' => $user->id
            ]);
            return redirect('/owner/dashboard')
                ->withErrors(['error' => 'Please create a distributor profile first.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_quantity' => 'nullable|integer|min:1',
            'image' => 'required|image|max:2048', // Made mandatory
        ]);

        \Log::info('[ProductController] Validation passed', [
            'product_name' => $validated['name'],
            'category_id' => $validated['category_id']
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            \Log::info('[ProductController] Image uploaded', ['path' => $imagePath]);
        }

        try {
            $product = Product::create([
                'distributor_id' => $distributor->id,
                'name' => $validated['name'],
                'sku' => 'PRD-' . strtoupper(Str::random(8)),
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'brand' => $validated['brand'] ?? null,
                'model' => $validated['model'] ?? null,
                'base_price' => $validated['price'],
                'wholesale_price' => $validated['wholesale_price'] ?? null,
                'wholesale_min_qty' => $validated['wholesale_min_quantity'] ?? null,
                'image_path' => $imagePath,
                'is_active' => true,
            ]);

            \Log::info('[ProductController] Product created successfully', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku
            ]);

            return redirect()->route('inventory.index')
                ->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            \Log::error('[ProductController] Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $distributor = auth()->user()->distributor;

        $product = Product::where('distributor_id', $distributor->id)
            ->with('category')
            ->findOrFail($id);

        $categories = Category::whereNull('parent_id')->select('id', 'name')->get();

        return Inertia::render('Owner/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $distributor = auth()->user()->distributor;

        $product = Product::where('distributor_id', $distributor->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_quantity' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image_path) {
                \Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('owner.products.index')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        $distributor = auth()->user()->distributor;

        $product = Product::where('distributor_id', $distributor->id)
            ->findOrFail($id);

        // Check if product has active inventory
        if ($product->inventory()->where('quantity', '>', 0)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete product with existing inventory.']);
        }

        $product->delete();

        return redirect()->route('owner.products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
