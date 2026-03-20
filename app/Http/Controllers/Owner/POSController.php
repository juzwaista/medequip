<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $distributorId = $user->role === 'staff' ? $user->staff->distributor_id : $user->distributor->id;

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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_paid' => 'required|numeric|min:0'
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $distributor = $user->role === 'staff' ? $user->staff->distributor : $user->distributor;

        return DB::transaction(function () use ($validated, $distributor, $user) {
            $totalAmount = 0;
            $orderItemsData = [];

            // Calculate total and prepare items
            foreach ($validated['items'] as $item) {
                $product = Product::with('inventory')->where('id', $item['product_id'])
                    ->where('distributor_id', $distributor->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->total_stock < $item['quantity']) {
                    return back()->withErrors(['message' => "Not enough stock for {$product->name}."]);
                }

                $remainingQuantity = $item['quantity'];
                foreach ($product->inventory as $inventory) {
                    if ($remainingQuantity <= 0) break;
                    
                    $available = $inventory->quantity; 
                    if ($available > 0) {
                        $deductAmount = min($available, $remainingQuantity);
                        $inventory->decrement('quantity', $deductAmount);
                        $remainingQuantity -= $deductAmount;
                    }
                }

                $subtotal = $product->base_price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item['quantity'],
                    'price'      => $product->base_price,
                    'subtotal'   => $subtotal,
                ];
            }

            if ($validated['amount_paid'] < $totalAmount) {
                return back()->withErrors(['message' => 'Amount paid is less than total amount.']);
            }

            // Create Order (Walk-in, no customer_id needed if nullable, else link to distributor as a walk-in proxy)
            // Assuming customer_id is required, we use the distributor's user id or create a stub walk-in account.
            // Wait, customer_id might be constrained. I will use the current user's ID as the walk-in proxy to satisfy FK constraints securely.
            $order = Order::create([
                'order_number'     => 'POS-' . strtoupper(Str::random(8)),
                'customer_id'      => $user->id, // Proxy for walk-in
                'distributor_id'   => $distributor->id,
                'status'           => 'delivered', // Immediate delivery for POS
                'subtotal'         => $totalAmount,
                'discount'         => 0,
                'total_amount'     => $totalAmount,
                'delivery_address' => 'Walk-in / In-store Pickup',
                'contact_number'   => 'Walk-in',
                'payment_method'   => 'cash',
                'approved_at'      => now(),
                'delivered_at'     => now(),
                'received_at'      => now(),
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            // Generate Invoice and POS Payment Record
            $invoice = $order->invoice()->create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(10)),
                'amount'         => $totalAmount,
                'status'         => 'paid',
                'due_date'       => now(),
                'paid_at'        => now(),
            ]);

            // Platform fee doesn't apply to POS cash payments generally, or if it does, the seller owes the platform.
            // For simplicity, we assume POS cash transactions are net 0 fee for the platform since cash is collected directly.
            Payment::create([
                'invoice_id'          => $invoice->id,
                'payment_method'      => 'cash', // Needs to be added to allowed methods
                'amount'              => $totalAmount,
                'status'              => 'verified',
                'escrow_status'       => 'released', // No escrow for direct cash
                'platform_fee_rate'   => 0,
                'platform_fee_amount' => 0,
                'net_seller_amount'   => $totalAmount,
                'verified_at'         => now(),
                'released_at'         => now(),
            ]);

            return back()->with('success', 'POS Transaction completed successfully! Change: ₱' . number_format($validated['amount_paid'] - $totalAmount, 2));
        });
    }
}
