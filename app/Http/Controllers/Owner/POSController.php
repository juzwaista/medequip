<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class POSController extends Controller
{
    public function index()
    {
        $distributor = $this->getDistributor();
        $distributorId = $distributor?->id;

        $products = Product::where('distributor_id', $distributorId)
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                $data = $product->toArray();
                $data['stock'] = $product->total_stock;
                return $data;
            });

        return Inertia::render('Owner/POS/Index', [
            'products' => $products
        ]);
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method'   => 'required|in:cash,paymongo',
            'amount_paid'      => 'required_if:payment_method,cash|nullable|numeric|min:0',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $distributor = $this->getDistributor();

        return DB::transaction(function () use ($validated, $distributor, $user, $request) {
            $totalAmount = 0;
            $orderItemsData = [];
            $lockedProducts = [];

            // Lock products and validate stock without mutating inventory first.
            foreach ($validated['items'] as $item) {
                $product = Product::with('inventory')
                    ->where('id', $item['product_id'])
                    ->where('distributor_id', $distributor->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->total_stock < $item['quantity']) {
                    return back()->withErrors(['message' => "Not enough stock for {$product->name}."]);
                }

                $subtotal = $product->base_price * $item['quantity'];
                $totalAmount += $subtotal;
                $lockedProducts[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                ];

                $orderItemsData[] = [
                    'product_id'   => $product->id,
                    'inventory_id' => null,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $product->base_price,
                    'total_price'  => $subtotal,
                    'subtotal'     => $subtotal,
                    'is_wholesale' => false,
                ];
            }

            // Cash validation
            if ($validated['payment_method'] === 'cash' && $validated['amount_paid'] < $totalAmount) {
                return back()->withErrors(['message' => 'Amount paid is less than total amount.']);
            }

            // Deduct stock only after all validation checks pass.
            foreach ($lockedProducts as $lineItem) {
                $remainingQuantity = $lineItem['quantity'];
                foreach ($lineItem['product']->inventory as $inventory) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }
                    $available = $inventory->quantity;
                    if ($available > 0) {
                        $deductAmount = min($available, $remainingQuantity);
                        $inventory->decrement('quantity', $deductAmount);
                        $remainingQuantity -= $deductAmount;
                    }
                }
            }

            $paymentMethod = $validated['payment_method'];

            // Create the POS order
            $order = Order::create([
                'order_number'     => 'POS-' . strtoupper(Str::random(8)),
                'customer_id'      => $user->id,
                'distributor_id'   => $distributor->id,
                'status'           => $paymentMethod === 'cash' ? 'completed' : 'pending',
                'subtotal'         => $totalAmount,
                'discount'         => 0,
                'total_amount'     => $totalAmount,
                'delivery_address' => 'Walk-in / In-store Pickup',
                'contact_number'   => 'Walk-in',
                'payment_method'   => $paymentMethod,
                'approved_at'      => now(),
                'delivered_at'     => $paymentMethod === 'cash' ? now() : null,
                'received_at'      => $paymentMethod === 'cash' ? now() : null,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // Create invoice
            $invoice = $order->invoice()->create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
                'subtotal'       => $totalAmount,
                'tax'            => 0,
                'discount'       => 0,
                'total_amount'   => $totalAmount,
                'status'         => $paymentMethod === 'cash' ? 'paid' : 'unpaid',
                'due_date'       => now()->addDays(1),
            ]);

            // ── CASH: Immediate settlement, no escrow ──────────────────────────
            if ($paymentMethod === 'cash') {
                $posPayment = Payment::create([
                    'invoice_id'          => $invoice->id,
                    'payment_method'      => 'cash',
                    'amount'              => $totalAmount,
                    'status'              => 'verified',
                    'escrow_status'       => 'released',
                    'platform_fee_rate'   => 0,
                    'platform_fee_amount' => 0,
                    'net_seller_amount'   => $totalAmount,
                    'verified_at'         => now(),
                    'released_at'         => now(),
                ]);
                $posPayment->creditSellerWalletOnVerification();

                $change = number_format($validated['amount_paid'] - $totalAmount, 2);
                return back()->with([
                    'success' => "✅ Cash sale complete! Change: ₱{$change}",
                    'receipt_url' => route('owner.orders.receipt', $order->id),
                ]);
            }

            // ── PAYMONGO: Create checkout session and redirect ─────────────────
            try {
                $paymongo = app(PayMongoService::class);

                // Create a pending payment record
                $fees = Payment::calculateFees($totalAmount);
                Payment::create([
                    'invoice_id'          => $invoice->id,
                    'payment_method'      => 'paymongo',
                    'amount'              => $totalAmount,
                    'status'              => 'pending',
                    'escrow_status'       => 'held',
                    'platform_fee_rate'   => $fees['platform_fee_rate'],
                    'platform_fee_amount' => $fees['platform_fee_amount'],
                    'net_seller_amount'   => $fees['net_seller_amount'],
                ]);

                $session = $paymongo->createCheckoutSession(
                    $invoice,
                    route('owner.pos.success', $invoice),
                    route('owner.pos.cancel',  $invoice)
                );

                // Store the session ID on the payment record
                Payment::where('invoice_id', $invoice->id)
                    ->where('status', 'pending')
                    ->update(['paymongo_session_id' => $session['session_id']]);

                // Redirect to PayMongo hosted checkout
                return Inertia::location($session['checkout_url']);

            } catch (\Exception $e) {
                Log::error('[POSController] PayMongo session failed', ['error' => $e->getMessage()]);
                return back()->withErrors(['message' => 'Online payment setup failed: ' . $e->getMessage()]);
            }
        });
    }

    /**
     * PayMongo redirects here after a successful POS online payment.
     */
    public function paymentSuccess(\App\Models\Invoice $invoice)
    {
        $distributor = $this->getDistributor();
        if (!$invoice->order || $invoice->order->distributor_id !== $distributor->id) {
            abort(403);
        }

        return redirect()->route('owner.pos.index')
            ->with([
                'success' => "✅ Online payment received for Invoice #{$invoice->invoice_number}! Funds have been added to your balance.",
                'receipt_url' => route('owner.orders.receipt', $invoice->order_id ?? 0),
            ]);
    }

    /**
     * PayMongo redirects here after a cancelled POS online payment.
     */
    public function paymentCancel(\App\Models\Invoice $invoice)
    {
        $distributor = $this->getDistributor();
        if (!$invoice->order || $invoice->order->distributor_id !== $distributor->id) {
            abort(403);
        }

        // Mark the pending payment as expired
        Payment::where('invoice_id', $invoice->id)
            ->where('paymongo_status', 'active')
            ->orWhere(function ($q) use ($invoice) {
                $q->where('invoice_id', $invoice->id)->where('status', 'pending');
            })
            ->update(['paymongo_status' => 'expired', 'status' => 'rejected']);

        return redirect()->route('owner.pos.index')
            ->with('error', '⚠️ Payment cancelled. The sale has been voided.');
    }
}
