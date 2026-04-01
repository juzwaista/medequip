<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    private function getCart(): array
    {
        return session()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        session()->put('cart', $cart);
    }

    /**
     * Show cart page
     */
    public function index()
    {
        $cart = CartService::pruneCart($this->getCart());
        $this->saveCart($cart);

        $cartItems = CartService::enrichCartItems($cart);
        $subtotal = CartService::calculateSubtotal($cartItems);
        $shippingFeePerOrder = (float) config('services.shipping.flat_fee', 50);
        $distributorCount = collect($cartItems)
            ->pluck('product.distributor_id')
            ->filter()
            ->unique()
            ->count();
        $shippingFee = count($cartItems) > 0 ? ($shippingFeePerOrder * max(1, $distributorCount)) : 0;

        return Inertia::render('Cart/Index', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'shipping_fee_per_order' => $shippingFeePerOrder,
            'distributor_count' => $distributorCount,
            'estimated_total' => $subtotal + $shippingFee,
        ]);
    }

    /**
     * Add product to cart
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_variation_id' => 'nullable|integer|exists:product_variations,id',
        ]);

        $product = Product::with(['inventory', 'variations', 'distributor'])->findOrFail($request->product_id);
        
        // Prevent adding products from suspended distributors
        if ($product->distributor->is_suspended) {
            return back()->with('error', 'This seller is currently suspended and cannot accept new orders at this time.');
        }

        $hasVariations = $product->variations()->where('is_active', true)->exists();
        $variationId = $request->input('product_variation_id');

        if ($hasVariations) {
            if (! $variationId) {
                return back()->with('error', 'Please select a product option before adding to cart.');
            }

            $variation = ProductVariation::where('id', $variationId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();

            if (! $variation) {
                return back()->with('error', 'Invalid product option.');
            }
        } else {
            $variationId = null;
        }

        $availableStock = CartService::availableStockForLine($product, $variationId ? (int) $variationId : null);

        if ($request->quantity > $availableStock) {
            return back()->with('error', 'Insufficient stock available');
        }

        $cart = CartService::normalizeCart($this->getCart());
        $lineKey = CartService::lineKey($product->id, $variationId ? (int) $variationId : null);

        if (isset($cart[$lineKey])) {
            $newQuantity = $cart[$lineKey]['quantity'] + $request->quantity;

            if ($newQuantity > $availableStock) {
                return back()->with('error', 'Cannot add more items - insufficient stock');
            }

            $cart[$lineKey]['quantity'] = $newQuantity;
        } else {
            $cart[$lineKey] = [
                'product_id' => $product->id,
                'product_variation_id' => $variationId ? (int) $variationId : null,
                'quantity' => $request->quantity,
            ];
        }

        $this->saveCart($cart);

        return back()->with('success', 'Product added to cart!');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, string $lineKey)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $lineKey = rawurldecode($lineKey);

        $cart = CartService::normalizeCart($this->getCart());

        if (! isset($cart[$lineKey])) {
            return back()->with('error', 'Cart item not found');
        }

        [$productId, $variationId] = CartService::parseLineKey($lineKey);

        $product = Product::with(['inventory', 'variations'])->findOrFail($productId);

        $availableStock = CartService::availableStockForLine($product, $variationId);

        if ($request->quantity > $availableStock) {
            return back()->with('error', 'Insufficient stock available');
        }

        $cart[$lineKey]['quantity'] = $request->quantity;
        $this->saveCart($cart);

        return back()->with('success', 'Cart updated');
    }

    /**
     * Remove item from cart
     */
    public function remove(string $lineKey)
    {
        $lineKey = rawurldecode($lineKey);
        $cart = CartService::normalizeCart($this->getCart());

        if (isset($cart[$lineKey])) {
            unset($cart[$lineKey]);
            $this->saveCart($cart);
        }

        return back()->with('success', 'Item removed from cart');
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        session()->forget('cart');

        return back()->with('success', 'Cart cleared');
    }

    /**
     * Get cart count (for header badge)
     */
    public function count()
    {
        $cart = CartService::normalizeCart($this->getCart());
        $uniqueItems = count($cart);

        return response()->json(['count' => $uniqueItems]);
    }
}
