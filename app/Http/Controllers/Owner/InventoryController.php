<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InventoryController extends Controller
{
    /**
     * Display unified inventory management (products + stock)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->distributor;

        Log::info('[InventoryController] Inventory page accessed', [
            'user_id' => $user->id,
            'has_distributor' => !is_null($distributor)
        ]);

        if (!$distributor) {
            return redirect('/owner/dashboard')
                ->withErrors(['error' => 'Please create a distributor profile first.']);
        }

        // Get products WITH their inventory totals
        $query = Product::where('distributor_id', $distributor->id)
            ->with(['category', 'inventory']);

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

        // Filter by stock status
        if ($request->stock_status) {
            $query->whereHas('inventory', function ($q) use ($request) {
                if ($request->stock_status === 'out') {
                    $q->havingRaw('SUM(quantity) = 0');
                } elseif ($request->stock_status === 'low') {
                    $q->havingRaw('SUM(quantity) > 0 AND SUM(quantity) < 10');
                } elseif ($request->stock_status === 'in_stock') {
                    $q->havingRaw('SUM(quantity) >= 10');
                }
            });
        }

        $products = $query->latest()->paginate(12);

        // Add computed stock data
        $products->getCollection()->transform(function ($product) {
            $totalStock = $product->inventory->sum('quantity');
            $totalReserved = $product->inventory->sum('reserved_quantity');
            $available = $totalStock - $totalReserved;

            $product->total_stock = $totalStock;
            $product->total_reserved = $totalReserved;
            $product->available_stock = $available;
            
            // Stock status
            if ($totalStock === 0) {
                $product->stock_status = 'out';
                $product->stock_color = 'red';
                $product->stock_label = 'Out of Stock';
            } elseif ($totalStock < 10) {
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
            'filters' => $request->only(['search', 'category_id', 'stock_status']),
        ]);
    }

    /**
     * Show form to create product with initial stock
     */
    public function create()
    {
        $distributor = auth()->user()->distributor;

        if (!$distributor) {
            return redirect('/owner/dashboard');
        }

        $categories = Category::whereNull('parent_id')->select('id', 'name')->get();

        return Inertia::render('Owner/Inventory/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store product AND initial inventory together
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $distributor = $user->distributor;

        Log::info('[InventoryController] Creating product with initial stock', [
            'user_id' => $user->id,
            'distributor_id' => $distributor?->id
        ]);

        if (!$distributor) {
            return redirect('/owner/dashboard')
                ->withErrors(['error' => 'Please create a distributor profile first.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
            'image' => 'nullable|image|max:2048',
            'initial_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                Log::info('[InventoryController] Product image uploaded', ['path' => $imagePath]);
            }

            // Create product
            $product = Product::create([
                'distributor_id' => $distributor->id,
                'name' => $validated['name'],
                'sku' => 'PRD-' . strtoupper(Str::random(8)),
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'base_price' => $validated['base_price'],
                'wholesale_price' => $validated['wholesale_price'] ?? null,
                'wholesale_min_qty' => $validated['wholesale_min_qty'] ?? null,
                'image_path' => $imagePath,
                'is_active' => true,
            ]);

            // Create initial inventory record
            Inventory::create([
                'product_id' => $product->id,
                'branch_id' => null,
                'quantity' => $validated['initial_quantity'],
                'reorder_level' => $validated['reorder_level'],
                'reserved_quantity' => 0,
            ]);

            Log::info('[InventoryController] Product and inventory created', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'initial_quantity' => $validated['initial_quantity']
            ]);

            return redirect('/owner/inventory')
                ->with('success', 'Product added to inventory successfully.');
        } catch (\Exception $e) {
            Log::error('[InventoryController] Failed to create product/inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form to edit product details
     */
    public function edit($id)
    {
        $distributor = auth()->user()->distributor;

        $inventory = Inventory::with(['product.category'])
            ->whereHas('product', function($query) use ($distributor) {
                $query->where('distributor_id', $distributor->id);
            })
            ->findOrFail($id);

        $categories = Category::whereNull('parent_id')->select('id', 'name')->get();

        return Inertia::render('Owner/Inventory/Edit', [
            'inventory' => $inventory,
            'categories' => $categories,
        ]);
    }

    /**
     * Update product details
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
            'base_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_min_qty' => 'nullable|integer|min:1',
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

        Log::info('[InventoryController] Product updated', [
            'product_id' => $product->id,
            'product_name' => $product->name
        ]);

        return redirect('/owner/inventory')
            ->with('success', 'Product updated successfully.');
    }

    /**
     * Quick stock adjustment
     */
    public function adjustStock(Request $request, $id)
    {
        $distributor = auth()->user()->distributor;

        $product = Product::where('distributor_id', $distributor->id)
            ->with('inventory')
            ->findOrFail($id);

        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'reason' => 'nullable|string|max:255',
        ]);

        // Get first inventory record or create one
        $inventory = $product->inventory->first();
        
        if (!$inventory) {
            $inventory = Inventory::create([
                'product_id' => $product->id,
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
     * Delete product and all inventory
     */
    public function destroy($id)
    {
        $distributor = auth()->user()->distributor;

        $product = Product::where('distributor_id', $distributor->id)
            ->with('inventory')
            ->findOrFail($id);

        // Check if any inventory has reserved stock
        $hasReserved = $product->inventory->where('reserved_quantity', '>', 0)->count() > 0;

        if ($hasReserved) {
            return back()->withErrors(['error' => 'Cannot delete product with reserved stock.']);
        }

        // Delete all inventory records
        $product->inventory()->delete();

        // Delete product image
        if ($product->image_path) {
            \Storage::disk('public')->delete($product->image_path);
        }

        // Delete product
        $product->delete();

        Log::info('[InventoryController] Product and inventory deleted', [
            'product_id' => $id
        ]);

        return redirect('/owner/inventory')
            ->with('success', 'Product removed from inventory.');
    }
}
