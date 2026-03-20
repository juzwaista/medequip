<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Inventory;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(private PayMongoService $paymongo) {}

    /**
     * Show checkout page.
     */
    public function checkout()
    {
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        $cartItems = $this->enrichCartItems($cart);
        $subtotal = $this->calculateSubtotal($cartItems);

        return Inertia::render('Checkout/Index', [
            'cartItems'      => $cartItems,
            'subtotal'       => $subtotal,
            'cities'         => config('cavite.cities'),
            'barangays'      => config('cavite.barangays'),
            'savedAddresses' => auth()->user()->addresses()->latest()->get(),
        ]);
    }

    /**
     * Place order with payment.
     *
     * Flow:
     *   PayMongo methods → create order + invoice + Payment record → redirect to PayMongo checkout
     *   Bank transfer    → create order + invoice + pending Payment → redirect to confirmation
     */
    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name'    => 'required|string|min:2|max:100',
            'delivery_address' => 'required|string|max:500|min:5',
            'contact_number'   => 'required|string|min:7|max:20',
            'notes'            => 'nullable|string|max:1000',
            'payment_method'   => 'required|in:card,gcash,paymaya',
        ], [
            'customer_name.required'    => 'Please provide the recipient name',
            'delivery_address.required' => 'Please provide a delivery address',
            'contact_number.required'   => 'Contact number is required for delivery',
            'payment_method.required'   => 'Please choose a payment method',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return back()->withErrors(['cart' => 'Your cart is empty'])->withInput();
        }

        $isPaymongo = in_array($validated['payment_method'], Payment::paymongoMethods());

        try {
            DB::beginTransaction();

            // Group cart items by distributor
            $ordersByDistributor = [];

            foreach ($cart as $productId => $cartItem) {
                $product = Product::with('inventory')->findOrFail($productId);
                $distributorId = $product->distributor_id;

                if (!isset($ordersByDistributor[$distributorId])) {
                    $ordersByDistributor[$distributorId] = [
                        'distributor_id' => $distributorId,
                        'items' => []
                    ];
                }

                $ordersByDistributor[$distributorId]['items'][] = [
                    'product'  => $product,
                    'quantity' => $cartItem['quantity'],
                ];
            }

            $createdOrders = [];
            $totalInvoiceForPaymongo = null;

            foreach ($ordersByDistributor as $distributorData) {
                $order = Order::create([
                    'customer_id'      => auth()->id(),
                    'distributor_id'   => $distributorData['distributor_id'],
                    'order_number'     => Order::generateOrderNumber(),
                    'status'           => 'pending',
                    'subtotal'         => 0,
                    'total_amount'     => 0,
                    'delivery_address' => $validated['delivery_address'],
                    'contact_number'   => $validated['contact_number'],
                    'notes'            => $validated['notes'] ?? null,
                    'payment_method'   => $validated['payment_method'],
                ]);

                $totalAmount = 0;

                foreach ($distributorData['items'] as $item) {
                    $product  = $item['product'];
                    $quantity = $item['quantity'];

                    $isWholesale = $product->wholesale_price && $product->wholesale_min_qty && $quantity >= $product->wholesale_min_qty;
                    $unitPrice   = $isWholesale ? $product->wholesale_price : $product->base_price;

                    // Reserve inventory
                    $inventory = $product->inventory()
                        ->whereRaw('(quantity - reserved_quantity) >= ?', [$quantity])
                        ->first();

                    if (!$inventory) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }

                    if (!$inventory->reserve($quantity)) {
                        throw new \Exception("Unable to reserve stock for {$product->name}");
                    }

                    $order->items()->create([
                        'product_id'   => $product->id,
                        'inventory_id' => $inventory->id,
                        'quantity'     => $quantity,
                        'unit_price'   => $unitPrice,
                        'total_price'  => $unitPrice * $quantity,
                        'is_wholesale' => $isWholesale,
                    ]);

                    $totalAmount += $unitPrice * $quantity;
                }

                $order->update([
                    'subtotal'     => $totalAmount,
                    'total_amount' => $totalAmount,
                ]);

                // Create invoice
                $invoice = Invoice::create([
                    'order_id'       => $order->id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'subtotal'       => $totalAmount,
                    'tax'            => 0,
                    'discount'       => 0,
                    'total_amount'   => $totalAmount,
                    'status'         => 'unpaid',
                    'due_date'       => now()->addDays(7),
                ]);

                // Create payment record
                $fees = Payment::calculateFees($totalAmount);

                // PayMongo — create payment record, session created below
                $payment = Payment::create([
                    'invoice_id'          => $invoice->id,
                    'payment_method'      => 'paymongo',
                    'amount'              => $totalAmount,
                    'status'              => 'pending',
                    'escrow_status'       => 'held',
                    'platform_fee_rate'   => $fees['platform_fee_rate'],
                    'platform_fee_amount' => $fees['platform_fee_amount'],
                    'net_seller_amount'   => $fees['net_seller_amount'],
                ]);

                $totalInvoiceForPaymongo = $invoice;

                $createdOrders[] = $order;
            }

            // Clear cart
            session()->forget('cart');

            DB::commit();

            // For PayMongo — redirect to checkout session
            if ($isPaymongo && $totalInvoiceForPaymongo) {
                try {
                    $session = $this->paymongo->createCheckoutSession(
                        $totalInvoiceForPaymongo,
                        route('orders.confirmation', $createdOrders[0]),
                        route('orders.confirmation', $createdOrders[0])
                    );

                    // Link session to payment
                    Payment::where('invoice_id', $totalInvoiceForPaymongo->id)
                        ->where('status', 'pending')
                        ->update([
                            'paymongo_session_id' => $session['session_id'],
                            'paymongo_status'     => 'active',
                        ]);

                    return Inertia::location($session['checkout_url']);
                } catch (\Exception $e) {
                    Log::error('[OrderController] PayMongo session failed', [
                        'error' => $e->getMessage(),
                    ]);
                    // Fallback: redirect to confirmation, payment can be retried
                    return redirect()->route('orders.confirmation', $createdOrders[0])
                        ->with('warning', 'Order placed, but payment initiation failed. Please try again from your orders page.');
                }
            }

            // Bank transfer — go to confirmation
            return redirect()->route('orders.confirmation', $createdOrders[0])
                ->with('success', 'Order placed! Your bank transfer is pending verification.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[OrderController] Order placement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['order' => 'Failed to place order: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Order confirmation page.
     */
    public function confirmation(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        $order->load(['items.product', 'distributor', 'invoice.payments']);

        return Inertia::render('Orders/Confirmation', [
            'order' => $order,
        ]);
    }

    /**
     * Buyer confirms receipt of delivered order → releases escrow.
     */
    public function confirmReceived(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if (!$order->canBeConfirmedReceived()) {
            return back()->with('error', 'This order cannot be confirmed yet.');
        }

        DB::transaction(function () use ($order) {
            // Mark order as received
            $order->update([
                'received_at' => now(),
                'status'      => 'completed',
            ]);

            // Release escrow for all verified payments on this order's invoice
            if ($order->invoice) {
                $order->invoice->payments()
                    ->where('status', 'verified')
                    ->where('escrow_status', 'held')
                    ->each(function ($payment) {
                        $payment->releaseEscrow();
                    });
            }

            Log::info('[OrderController] Buyer confirmed receipt, escrow released', [
                'order_id' => $order->id,
            ]);
        });

        return back()->with('success', 'Delivery confirmed! Thank you for your order.');
    }

    /**
     * Enrich cart items with product data.
     */
    private function enrichCartItems($cart)
    {
        $items = [];

        foreach ($cart as $productId => $cartItem) {
            $product = Product::with(['distributor', 'images', 'inventory'])->find($productId);
            if (!$product) continue;

            $quantity    = $cartItem['quantity'];
            $isWholesale = $product->hasWholesalePricing() && $quantity >= $product->wholesale_min_qty;
            $unitPrice   = $isWholesale ? $product->wholesale_price : $product->base_price;

            $items[] = [
                'product'      => $product,
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
                'is_wholesale' => $isWholesale,
                'subtotal'     => $unitPrice * $quantity,
            ];
        }

        return $items;
    }

    /**
     * Calculate cart subtotal.
     */
    private function calculateSubtotal($cartItems)
    {
        return array_sum(array_column($cartItems, 'subtotal'));
    }
}
