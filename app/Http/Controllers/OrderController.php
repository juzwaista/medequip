<?php

namespace App\Http\Controllers;

use App\Models\CheckoutBatch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Notifications\OrderNotification;
use App\Rules\SafeUpload;
use App\Services\CartService;
use App\Services\CustomerReliabilityService;
use App\Services\OrderInvoiceService;
use App\Services\PayMongoService;
use App\Services\PrescriptionChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function __construct(
        private PayMongoService $paymongo,
        private OrderInvoiceService $orderInvoiceService,
    ) {}

    /**
     * Determine shipping fee base rate and highest required vehicle for an array of cart items.
     * Returns ['fee' => float, 'vehicle' => string]
     */
    private function calculateShippingRequirement(iterable $cartItems): array
    {
        $vehicleHierarchy = [
            'motorcycle' => 1,
            'car_sedan' => 2,
            'car_hatchback' => 3,
            'pickup_truck' => 4,
            'box_truck' => 5,
        ];

        $shippingBases = [
            'motorcycle' => 60,
            'car_sedan' => 150,
            'car_hatchback' => 150,
            'pickup_truck' => 500,
            'box_truck' => 1000,
        ];

        $highestWeight = 1;
        $highestVehicle = 'motorcycle';

        foreach ($cartItems as $item) {
            // $item can be an array output from CartService or an array shaped for placeOrder
            $product = is_array($item) ? $item['product'] : $item;
            $req = $product->vehicle_requirement ?? 'motorcycle';
            $weight = $vehicleHierarchy[$req] ?? 1;
            if ($weight > $highestWeight) {
                $highestWeight = $weight;
                $highestVehicle = $req;
            }
        }

        return [
            'fee' => (float) ($shippingBases[$highestVehicle] ?? 60),
            'vehicle' => $highestVehicle,
        ];
    }

    /**
     * Show checkout page.
     */
    public function checkout()
    {
        // Handle "Buy Now" - isolate single product if params present
        $isBuyNow = request()->boolean('buy_now') && request()->has('product_id');
        
        if ($isBuyNow) {
            $buyNowItem = [
                'product_id' => request('product_id'),
                'product_variation_id' => request('product_variation_id'),
                'quantity' => request('quantity', 1),
            ];
            $cart = [\App\Services\CartService::lineKey($buyNowItem['product_id'], $buyNowItem['product_variation_id']) => $buyNowItem];
        } else {
            $cart = CartService::pruneCart(session()->get('cart', []));
            
            // Handle Selective Checkout from Cart
            if (request()->has('selected_items')) {
                $selectedKeys = explode(',', request('selected_items'));
                $cart = array_intersect_key($cart, array_flip($selectedKeys));
            }
        }

        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty');
        }

        $cartItems = CartService::enrichCartItems($cart);
        $subtotal = CartService::calculateSubtotal($cartItems);

        $distributorCount = collect($cartItems)
            ->pluck('product.distributor_id')
            ->filter()
            ->unique()
            ->count();

        $shippingFeeTotal = 0;
        $codLimitExceeded = false;
        $codLimitMessage = '';

        $groupedCart = collect($cartItems)->groupBy('product.distributor_id');
        foreach ($groupedCart as $distributorId => $dItems) {
            $req = $this->calculateShippingRequirement($dItems->all());
            $shippingFeeTotal += $req['fee'];

            $distributor = \App\Models\Distributor::find($distributorId);
            if ($distributor && $distributor->max_cod_amount > 0) {
                $orderSubtotal = (float) $dItems->sum(function ($item) {
                    $p = $item['product'];
                    $qty = $item['quantity'];
                    $v = $item['variation'] ?? null;
                    $isWholesale = $p->wholesale_price && $p->wholesale_min_qty && $qty >= $p->wholesale_min_qty;
                    $base = $isWholesale ? (float) $p->wholesale_price : (float) $p->base_price;
                    $adj = $v ? (float) $v->price_adjustment : 0.0;
                    return round($base + $adj, 2) * $qty;
                });

                $orderTotal = $orderSubtotal + $req['fee'];
                if ($orderTotal > $distributor->max_cod_amount) {
                    $codLimitExceeded = true;
                    $codLimitMessage = "Order total for {$distributor->company_name} (₱" . number_format($orderTotal, 2) . ") exceeds their COD limit of ₱" . number_format($distributor->max_cod_amount, 2) . ".";
                }
            }
        }

        $cartHasPrescriptionItems = collect($cartItems)->contains(fn ($line) => $line['product']->requires_prescription);

        $reliability = app(CustomerReliabilityService::class);
        $user = auth()->user();

        return Inertia::render('Checkout/Index', [
            'cartItems' => $cartItems,
            'cart_has_prescription_items' => $cartHasPrescriptionItems,
            'subtotal' => $subtotal,
            'shipping_fee_total' => $shippingFeeTotal,
            'estimated_total' => $subtotal + $shippingFeeTotal,
            'distributor_count' => $distributorCount,
            'cities' => config('cavite.cities'),
            'barangays' => config('cavite.barangays'),
            'savedAddresses' => auth()->user()->addresses()->latest()->get(),
            'wallet_balance' => (float) (auth()->user()->wallet?->balance ?? 0),
            'cod_available' => false, // Forced false to hide COD for now
            'cod_limit_exceeded' => $codLimitExceeded,
            'cod_limit_message' => $codLimitMessage,
            'cod_rejection_rate_percent' => $reliability->codRejectionRatePercent($user),
            'queryParams' => request()->all(),
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
            'customer_name' => 'required|string|min:2|max:100',
            'delivery_address' => 'required|string|max:500|min:5',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'delivery_latitude' => 'required|numeric|between:14.00,14.60',
            'delivery_longitude' => 'required|numeric|between:120.50,121.20',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:card,gcash,paymaya,wallet', // 'cod' removed from allowed list
            'fulfillment_method' => 'required|in:delivery,pickup',
            'buy_now' => 'nullable', // Handle as truthy check later
            'product_id' => 'required_if:buy_now,1,true|nullable|exists:products,id',
            'product_variation_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'required_if:buy_now,1,true|nullable|integer|min:1',
            'tin' => ['nullable', 'string', 'regex:/^[0-9]{3}-[0-9]{3}-[0-9]{3}-[0-9]{3}$/'],
            'selected_items' => 'nullable|string', // Comma-separated list of line keys
            
            // SC/PWD validation
            'apply_discount' => 'nullable', // Use manual boolean check to avoid standard boolean rule quirks with FormData
            'discount_type' => 'required_if:apply_discount,1,true|nullable|in:senior,pwd',
            'discount_id_number' => 'required_if:apply_discount,1,true|nullable|string|max:50',
            'discount_id_name' => 'required_if:apply_discount,1,true|nullable|string|max:100',
            'discount_id_image' => 'required_if:apply_discount,1,true|nullable|image|max:8192',
            'discount_terms' => 'exclude_unless:apply_discount,1,true|accepted',
            
            // Rx identity validation (if items require Rx)
            'prescription_patient_name' => 'nullable|string|max:100',
            'prescription_id_image' => 'nullable|image|max:8192',
            'prescription_image' => 'nullable|image|max:8192',
            'ocr_results' => 'nullable', // Can be array from Inertia or string from hidden input
        ], [
            'customer_name.required' => 'Please provide the recipient name',
            'delivery_address.required' => 'Please provide a delivery address',
            'contact_number.required' => 'Contact number is required for delivery',
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09, and contain numbers only.',
            'payment_method.required' => 'Please choose a payment method',
            'tin.regex' => 'TIN must follow the format 000-000-000-000',
            'discount_id_number.required_if' => 'ID number is required for the discount.',
            'discount_id_name.required_if' => 'Full name on ID is required for the discount.',
            'discount_id_image.required_if' => 'ID photo is required for the discount.',
            'discount_terms.accepted' => 'You must certify that this purchase is for your personal use to apply the discount.',
            'discount_id_image.image' => 'The document uploaded must be an image (PNG, JPG, etc.).',
        ]);

        $isBuyNow = filter_var($request->input('buy_now'), FILTER_VALIDATE_BOOLEAN) && $request->has('product_id');
        $applyDiscount = filter_var($request->input('apply_discount'), FILTER_VALIDATE_BOOLEAN);

        $user = auth()->user();
        $tin = $request->input('tin');

        // If TIN is empty in request, try to get it from user profile
        if (empty($tin) && $user && !empty($user->tin)) {
            $tin = $user->tin;
        }

        // Save TIN to user profile if it's new or changed
        if (!empty($tin) && $user && $user->tin !== $tin) {
            $user->update(['tin' => $tin]);
        }

        if ($isBuyNow) {
            $buyNowItem = [
                'product_id' => $request->product_id,
                'product_variation_id' => $request->product_variation_id,
                'quantity' => (int) $request->quantity,
            ];
            // Force $cart to ONLY contain the Buy Now item
            $cart = [\App\Services\CartService::lineKey($buyNowItem['product_id'], $buyNowItem['product_variation_id']) => $buyNowItem];
        } else {
            $cart = CartService::pruneCart(session()->get('cart', []));

            // Handle Selective Checkout
            if ($request->has('selected_items')) {
                $selectedKeys = explode(',', $request->input('selected_items'));
                $cart = array_intersect_key($cart, array_flip($selectedKeys));
            }
        }

        if (empty($cart)) {
            Log::warning('[OrderController] PlaceOrder failed: Cart is empty.', [
                'user_id' => auth()->id(),
                'is_buy_now' => $isBuyNow,
                'product_id' => $request->product_id,
                'session_cart' => session()->get('cart'),
            ]);
            return back()->withErrors(['cart' => 'Your cart is empty'])->withInput();
        }

        $isPaymongo = in_array($validated['payment_method'], Payment::paymongoMethods());
        $isWallet = $validated['payment_method'] === 'wallet';
        $isCod = $validated['payment_method'] === 'cod';

        /* COD logic disabled for now
        if ($isCod && ! app(CustomerReliabilityService::class)->isCodAllowedForCustomer($request->user())) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment_method' => ['Cash on delivery is not available for your account based on past order outcomes. Please choose another payment method.'],
            ]);
        }
        */

        $ocrResults = $request->input('ocr_results');
        if (is_string($ocrResults)) {
            $ocrResults = json_decode($ocrResults, true) ?? [];
        } elseif (!is_array($ocrResults)) {
            $ocrResults = [];
        }

        try {
            DB::beginTransaction();

            // Group cart items by distributor
            $ordersByDistributor = [];

            foreach ($cart as $lineKey => $cartItem) {
                [$productId, $variationId] = CartService::parseLineKey($lineKey);

                $product = Product::with(['inventory', 'variations'])->lockForUpdate()->findOrFail($productId);
                $distributorId = $product->distributor_id;

                $variation = null;
                if ($variationId) {
                    $variation = $product->variations->firstWhere('id', $variationId);
                    if (! $variation || ! $variation->is_active) {
                        throw new \Exception('Invalid product option in cart. Please refresh and try again.');
                    }
                } elseif ($product->variations->where('is_active', true)->isNotEmpty()) {
                    throw new \Exception('Please select a product option for '.$product->name);
                }

                if (! isset($ordersByDistributor[$distributorId])) {
                    $ordersByDistributor[$distributorId] = [
                        'distributor_id' => $distributorId,
                        'items' => [],
                    ];
                }

                $ordersByDistributor[$distributorId]['items'][] = [
                    'product' => $product,
                    'quantity' => $cartItem['quantity'],
                    'variation_id' => $variationId,
                    'variation' => $variation,
                ];
            }

            $walletToDebitTotal = 0;
            $customerWallet = auth()->user()->wallet;

            $createdOrders = [];

            foreach ($ordersByDistributor as $distributorData) {
                $distributor = \App\Models\Distributor::findOrFail($distributorData['distributor_id']);
                $isPickup = $validated['fulfillment_method'] === 'pickup';
                $shippingReq = $isPickup ? ['fee' => 0, 'vehicle' => 'motorcycle'] : $this->calculateShippingRequirement($distributorData['items']);
                
                $itemSubtotal = 0;
                foreach ($distributorData['items'] as $item) {
                    $product = $item['product'];
                    $quantity = $item['quantity'];
                    $variation = $item['variation'] ?? null;
                    $isWholesale = $product->wholesale_price && $product->wholesale_min_qty && $quantity >= $product->wholesale_min_qty;
                    $base = $isWholesale ? (float) $product->wholesale_price : (float) $product->base_price;
                    $adjustment = $variation ? (float) $variation->price_adjustment : 0.0;
                    $itemSubtotal += round($base + $adjustment, 2) * $quantity;
                }
                
                $orderTotal = $itemSubtotal + $shippingReq['fee'];

                $order = Order::create([
                    'customer_id' => auth()->id(),
                    'distributor_id' => $distributorData['distributor_id'],
                    'order_number' => Order::generateOrderNumber(),
                    'status' => 'pending',
                    'subtotal' => 0,
                    'total_amount' => 0,
                    'delivery_address' => $validated['delivery_address'],
                    'delivery_latitude' => $validated['delivery_latitude'] ?? null,
                    'delivery_longitude' => $validated['delivery_longitude'] ?? null,
                    'contact_number' => $validated['contact_number'],
                    'notes' => $validated['notes'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'fulfillment_method' => $validated['fulfillment_method'],
                    'pickup_instructions' => $isPickup ? $distributor->pickup_instructions : null,
                    'required_vehicle_type' => $shippingReq['vehicle'],
                    'tin' => $tin,
                    'discount_type' => $applyDiscount ? ($request->discount_type ?? 'senior') : 'none',
                    'discount_id_number' => $applyDiscount ? $request->discount_id_number : null,
                    'discount_id_name' => $applyDiscount ? $request->discount_id_name : null,
                    'discount_status' => $applyDiscount ? Order::DISCOUNT_PENDING : Order::DISCOUNT_NONE,
                    'prescription_patient_name' => $request->prescription_patient_name,
                    'ocr_results' => $ocrResults,
                ]);

                if ($request->hasFile('discount_id_image')) {
                    $order->update(['discount_id_image_path' => $request->file('discount_id_image')->store('discounts', 'public')]);
                }
                if ($request->hasFile('prescription_id_image')) {
                    $order->update(['prescription_id_image_path' => $request->file('prescription_id_image')->store('prescriptions', 'public')]);
                }
                if ($request->hasFile('prescription_image')) {
                    $order->update([
                        'prescription_image_path' => $request->file('prescription_image')->store('prescriptions', 'public'),
                        'prescription_status' => Order::PRESCRIPTION_PENDING_REVIEW
                    ]);
                }

                $itemSubtotal = 0;
                $vatableItemsTotal = 0;
                $exemptItemsTotal = 0;

                foreach ($distributorData['items'] as $item) {
                    $product = $item['product'];
                    $quantity = $item['quantity'];
                    $variationId = $item['variation_id'] ?? null;
                    $variation = $item['variation'] ?? null;

                    $isWholesale = $product->wholesale_price && $product->wholesale_min_qty && $quantity >= $product->wholesale_min_qty;
                    $base = $isWholesale ? (float) $product->wholesale_price : (float) $product->base_price;
                    $adjustment = $variation ? (float) $variation->price_adjustment : 0.0;
                    $unitPrice = round($base + $adjustment, 2);

                    // Reserve inventory
                    $inventoryQuery = $product->inventory()
                        ->whereRaw('(quantity - reserved_quantity) >= ?', [$quantity]);

                    if ($variationId) {
                        $inventoryQuery->where('product_variation_id', $variationId);
                    } else {
                        $inventoryQuery->whereNull('product_variation_id');
                    }

                    $inventory = $inventoryQuery->first();

                    if (! $inventory) {
                        throw new \Exception("Insufficient stock for {$product->name}");
                    }

                    if (! $inventory->reserve($quantity)) {
                        throw new \Exception("Unable to reserve stock for {$product->name}");
                    }

                    $lineTotal = $unitPrice * $quantity;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_variation_id' => $variationId,
                        'inventory_id' => $inventory->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $lineTotal,
                        'subtotal' => $lineTotal,
                        'is_wholesale' => $isWholesale,
                    ]);

                    $itemSubtotal += $lineTotal;
                    if ($product->is_vat_exempt) {
                        $exemptItemsTotal += $lineTotal;
                    } else {
                        $vatableItemsTotal += $lineTotal;
                    }
                }

                $shippingFee = (float) $shippingReq['fee'];
                $totalAmount = $itemSubtotal + $shippingFee;

                // VAT and Discount Calculation
                $isDiscounted = $order->discount_type !== 'none';
                
                if ($isDiscounted) {
                    // SC/PWD are VAT Exempt + 20% Discount
                    // 1. All item sales become VAT Exempt
                    // 2. 20% discount is applied to the VAT-exclusive price
                    
                    // Standard PH SC/PWD Discount:
                    // 1. VAT-Exempt on vatable items (Item / 1.12)
                    // 2. 20% discount on the net-of-VAT amount
                    
                    $netVatableItems = $vatableItemsTotal / 1.12;
                    $totalNetPrice = $netVatableItems + $exemptItemsTotal;
                    $discountAmount = $totalNetPrice * 0.20;
                    
                    $orderVatableSales = $shippingFee / 1.12; 
                    $orderVatAmount = $shippingFee - $orderVatableSales;
                    $orderVatExemptSales = $totalNetPrice;
                    
                    $newItemSubtotal = $totalNetPrice - $discountAmount;
                    $totalAmount = $newItemSubtotal + $shippingFee;

                    $order->update([
                        'subtotal' => round($newItemSubtotal, 2),
                        'shipping_fee' => round($shippingFee, 2),
                        'discount' => round($discountAmount, 2),
                        'total_amount' => round($totalAmount, 2),
                        'vatable_sales' => round($orderVatableSales, 2),
                        'vat_amount' => round($orderVatAmount, 2),
                        'vat_exempt_sales' => round($orderVatExemptSales, 2),
                    ]);
                } else {
                    // VAT Extraction Logic (Inclusive VAT)
                    // Shipping is vatable 12%
                    $totalVatableAmount = $vatableItemsTotal + $shippingFee;
                    $orderVatableSales = $totalVatableAmount / 1.12;
                    $orderVatAmount = $totalVatableAmount - $orderVatableSales;
                    $orderVatExemptSales = $exemptItemsTotal;

                    $order->update([
                        'subtotal' => $itemSubtotal,
                        'shipping_fee' => $shippingFee,
                        'total_amount' => $totalAmount,
                        'vatable_sales' => round($orderVatableSales, 2),
                        'vat_amount' => round($orderVatAmount, 2),
                        'vat_exempt_sales' => round($orderVatExemptSales, 2),
                    ]);
                }

                $order->load('items.product');
                $needsRx = $order->items->contains(fn ($line) => $line->product->requires_prescription);
                $order->update([
                    'prescription_status' => $needsRx
                        ? Order::PRESCRIPTION_AWAITING_UPLOAD
                        : Order::PRESCRIPTION_NOT_REQUIRED,
                ]);

                // Always create invoice for record-keeping (including COD).
                // OrderInvoiceService handles COD-specific payment method / status.
                $this->orderInvoiceService->createInvoiceAndPayment($order->fresh());
                if ($isWallet) {
                    $walletToDebitTotal += $totalAmount;
                }

                $createdOrders[] = $order->fresh();
            }

            if ($isWallet) {
                if (! $customerWallet || $customerWallet->balance < $walletToDebitTotal) {
                    throw new \Exception('Insufficient wallet balance for this checkout.');
                }

                $customerWallet->debit(
                    $walletToDebitTotal,
                    'order_payment',
                    (string) ($createdOrders[0]->id ?? uniqid('order_', true)),
                    'Wallet payment for order checkout'
                );
            }

            // Clear cart if NOT buy now (don't clear if they just bought one thing specifically)
            if (!$request->boolean('buy_now')) {
                session()->forget('cart');
            }

            DB::commit();

            foreach ($createdOrders as $placed) {
                $placed->loadMissing(['distributor.user', 'customer']);

                if ($placed->distributor?->user) {
                    $placed->distributor->user->notify(new OrderNotification($placed, 'order_placed'));
                }

                if ($placed->needsPrescriptionUpload() && $placed->customer) {
                    $placed->customer->notify(new OrderNotification($placed, 'order_requires_prescription'));
                }
            }

            $ordersWithInvoices = collect($createdOrders)->filter(fn ($o) => $o->invoice);

            // For PayMongo — create a single checkout for all created invoices (includes Rx orders; pay first, then upload prescription).
            if ($isPaymongo && $ordersWithInvoices->isNotEmpty()) {
                try {
                    $invoiceIds = $ordersWithInvoices
                        ->map(fn ($o) => $o->invoice?->id)
                        ->filter()
                        ->values()
                        ->all();
                    $orderIds = $ordersWithInvoices->pluck('id')->values()->all();
                    $batchTotal = $ordersWithInvoices->sum(fn ($o) => (float) $o->total_amount);

                    $batch = CheckoutBatch::create([
                        'user_id' => auth()->id(),
                        'primary_order_id' => $ordersWithInvoices->first()->id,
                        'paymongo_session_id' => 'pending-'.uniqid(),
                        'order_ids' => $orderIds,
                        'invoice_ids' => $invoiceIds,
                        'total_amount' => $batchTotal,
                        'status' => 'pending',
                    ]);

                    // Build comprehensive line items for the entire batch
                    $batchLineItems = [];
                    foreach ($ordersWithInvoices as $o) {
                        $o->loadMissing(['items.product', 'items.productVariation']);
                        foreach ($o->items as $item) {
                            $name = $item->product->name;
                            if ($item->productVariation) {
                                $name .= ' (' . $item->productVariation->display_label . ')';
                            }

                            $batchLineItems[] = [
                                'currency'    => 'PHP',
                                'amount'      => (int) round((float) $item->unit_price * 100),
                                'description' => $name,
                                'name'        => $name,
                                'quantity'    => $item->quantity,
                            ];
                        }
                        
                        if ($o->shipping_fee > 0) {
                            $batchLineItems[] = [
                                'currency'    => 'PHP',
                                'amount'      => (int) round((float) $o->shipping_fee * 100),
                                'description' => 'Shipping for order ' . $o->order_number,
                                'name'        => 'Shipping fee (' . ($o->distributor->company_name ?? 'Seller') . ')',
                                'quantity'    => 1,
                            ];
                        }

                        if ($o->discount > 0) {
                            $batchLineItems[] = [
                                'currency'    => 'PHP',
                                'amount'      => (int) round((float) $o->discount * -100),
                                'description' => ($o->discount_type === 'senior' ? 'Senior Citizen' : 'PWD') . ' Discount for ' . $o->order_number,
                                'name'        => ($o->discount_type === 'senior' ? 'Senior Citizen' : 'PWD') . ' Discount',
                                'quantity'    => 1,
                            ];
                        }
                    }

                    // Now that batch exists, create a final checkout session with callback URLs containing batch id.
                    $session = $this->paymongo->createGenericCheckoutSession(
                        description: 'MedEquip Checkout (' . count($orderIds) . ' order' . (count($orderIds) > 1 ? 's' : '') . ')',
                        amountCentavos: (int) round($batchTotal * 100),
                        successUrl: route('payment.batch.success', ['batch' => $batch->id]),
                        cancelUrl: route('payment.batch.cancel', ['batch' => $batch->id]),
                        metadata: [
                            'batch_id' => $batch->id,
                            'invoice_ids' => implode(',', $invoiceIds),
                            'order_ids' => implode(',', $orderIds),
                        ],
                        lineItems: $batchLineItems
                    );

                    $batch->update(['paymongo_session_id' => $session['session_id']]);

                    Payment::whereIn('invoice_id', $invoiceIds)
                        ->where('status', 'pending')
                        ->update(['paymongo_status' => 'active']);

                    return Inertia::location($session['checkout_url']);
                } catch (\Exception $e) {
                    Log::error('[OrderController] PayMongo session failed', [
                        'error' => $e->getMessage(),
                    ]);

                    // Fallback: redirect to details, payment can be retried
                    return redirect()->route('orders.show', $createdOrders[0])
                        ->with('warning', 'Order placed, but payment initiation failed. Please try again from this page.');
                }
            }

            if ($isWallet) {
                return redirect()->route('orders.confirmation', $createdOrders[0])
                    ->with('confirmation_order_ids', collect($createdOrders)->pluck('id')->values()->all())
                    ->with('success', 'Order placed! Your payment is held by the platform until you confirm delivery.');
            }

            if ($isCod) {
                return redirect()->route('orders.confirmation', $createdOrders[0])
                    ->with('confirmation_order_ids', collect($createdOrders)->pluck('id')->values()->all())
                    ->with('success', 'Order placed! Your courier will collect payment on delivery.');
            }

            return redirect()->route('orders.confirmation', $createdOrders[0])
                ->with('confirmation_order_ids', collect($createdOrders)->pluck('id')->values()->all())
                ->with('success', 'Order placed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[OrderController] Order placement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['order' => 'Failed to place order: '.$e->getMessage()])->withInput();
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

        $requestedIds = collect(session('confirmation_order_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if (! $requestedIds->contains($order->id)) {
            $requestedIds->prepend($order->id);
        }

        $orders = Order::with(['items.product', 'items.productVariation', 'distributor', 'invoice.payments'])
            ->where('customer_id', auth()->id())
            ->whereIn('id', $requestedIds->all())
            ->orderByDesc('created_at')
            ->get()
            ->values();

        $primaryOrder = $orders->firstWhere('id', $order->id) ?? $orders->first();
        $shippingTotal = (float) $orders->sum('shipping_fee');
        $grandTotal = (float) $orders->sum('total_amount');
        $itemsCount = (int) $orders->sum(fn ($o) => $o->items->count());

        return Inertia::render('Orders/Confirmation', [
            'order' => $primaryOrder,
            'orders' => $orders,
            'summary' => [
                'shops_count' => $orders->count(),
                'items_count' => $itemsCount,
                'shipping_total' => $shippingTotal,
                'grand_total' => $grandTotal,
            ],
        ]);
    }

    /**
     * Buyer confirms receipt of delivered order → releases funds held by the platform to the seller.
     */
    public function confirmReceived(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if (! $order->canBeConfirmedReceived()) {
            return back()->with('error', 'This order cannot be confirmed yet.');
        }

        try {
            DB::transaction(function () use ($order) {
                $order->update([
                    'received_at' => now(),
                    'status' => 'completed',
                ]);

                if ($order->invoice) {
                    $order->invoice->payments()
                        ->where('status', 'verified')
                        ->where('escrow_status', 'held')
                        ->each(function ($payment) {
                            $payment->releaseEscrow();
                        });

                    // Reconcile invoice status — mark as paid once all escrow is released.
                    $totalPaid = $order->invoice->payments()->where('status', 'verified')->sum('amount');
                    if ($totalPaid >= (float) $order->invoice->total_amount) {
                        $order->invoice->update(['status' => 'paid']);
                    }
                }

                Log::info('[OrderController] Buyer confirmed receipt; platform hold released for order', [
                    'order_id' => $order->id,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('[OrderController] confirmReceived payout failed', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'We could not finalize the seller payout yet. Nothing was charged again — please try again shortly or contact support if this continues.'
            );
        }

        $order->loadMissing('distributor.user');
        if ($order->distributor?->user) {
            $order->distributor->user->notify(new OrderNotification($order, 'order_completed'));
        }

        return back()->with('success', 'Delivery confirmed! Thank you for your order.');
    }

    /**
     * Retry payment for an unpaid order.
     */
    public function payNow(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if (in_array($order->status, ['cancelled', 'rejected'])) {
            return back()->withErrors(['payment' => 'Cannot pay for cancelled or rejected orders.']);
        }

        $order->load('invoice.payments');
        $invoice = $order->invoice;
        if (! $invoice && $order->hasOnlinePayment() && $order->payment_method === 'paymongo') {
            $this->orderInvoiceService->createInvoiceAndPayment($order->fresh());
            $order->refresh()->load('invoice.payments');
            $invoice = $order->invoice;
        }
        if (! $invoice) {
            return back()->withErrors(['payment' => 'Invoice not found for this order.']);
        }

        if ($invoice->status === 'paid') {
            return back()->with('info', 'This order is already paid.');
        }

        $payment = $invoice->payments()
            ->whereIn('status', ['pending', 'rejected'])
            ->latest()
            ->first();

        if (! $payment) {
            $fees = Payment::calculateFees((float) $invoice->total_amount);
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'payment_method' => 'paymongo',
                'amount' => $invoice->total_amount,
                'status' => 'pending',
                'escrow_status' => 'held',
                'platform_fee_rate' => $fees['platform_fee_rate'],
                'platform_fee_amount' => $fees['platform_fee_amount'],
                'net_seller_amount' => $fees['net_seller_amount'],
                'paymongo_status' => 'pending',
            ]);
        } else {
            $payment->update([
                'status' => 'pending',
                'paymongo_status' => 'pending',
                'paymongo_session_id' => null,
            ]);
        }

        try {
            $session = $this->paymongo->createCheckoutSession(
                $invoice,
                route('payment.success', $invoice),
                route('payment.cancel', $invoice)
            );

            $payment->update([
                'paymongo_session_id' => $session['session_id'],
                'paymongo_status' => 'active',
            ]);

            return Inertia::location($session['checkout_url']);
        } catch (\Exception $e) {
            Log::error('[OrderController] PayNow session failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Could not initiate payment. Please try again later.');
        }
    }

    /**
     * Customer: upload prescription photo (Rx-required orders).
     */
    public function prescriptionUploadForm(Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($order->prescription_status !== Order::PRESCRIPTION_AWAITING_UPLOAD) {
            return redirect()->route('orders.show', $order)
                ->with('info', 'This order does not require a prescription upload right now.');
        }

        return Inertia::render('Orders/PrescriptionUpload', [
            'order' => $order->load(['distributor', 'items.product']),
        ]);
    }

    /**
     * Customer: store prescription image.
     */
    public function prescriptionUpload(Request $request, Order $order)
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($order->prescription_status !== Order::PRESCRIPTION_AWAITING_UPLOAD) {
            return back()->withErrors(['prescription' => 'You cannot upload a prescription for this order at this time.']);
        }

        $request->validate([
            'prescription' => ['required', 'image', 'max:8192', SafeUpload::image()],
            'prescription_patient_name' => ['required', 'string', 'max:100'],
            'prescription_id_image' => ['required', 'image', 'max:8192', SafeUpload::image()],
        ], [
            'prescription.required' => 'Please choose a clear photo of your prescription.',
            'prescription_patient_name.required' => 'Please provide the patient name as written on the ID.',
            'prescription_id_image.required' => 'Please provide a photo of a valid ID for verification.',
        ]);

        $path = $request->file('prescription')->store('prescriptions', 'public');
        $idPath = $request->file('prescription_id_image')->store('prescriptions', 'public');

        $order->update([
            'prescription_image_path' => $path,
            'prescription_id_image_path' => $idPath,
            'prescription_patient_name' => $request->prescription_patient_name,
            'prescription_status' => Order::PRESCRIPTION_PENDING_REVIEW,
        ]);

        $order = $order->fresh();
        app(PrescriptionChatService::class)->postCustomerUpload($order);

        $order->loadMissing('distributor.user');
        if ($order->distributor?->user) {
            $order->distributor->user->notify(new OrderNotification($order, 'prescription_uploaded'));
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Prescription submitted. The distributor will review it before you can pay.');
    }
}
