<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Notifications\OrderNotification;
use App\Services\OrderChatAutomationService;
use App\Services\OrderPrescriptionRefundService;
use App\Services\PrescriptionChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display orders list
     */
    public function index(Request $request)
    {
        $distributor = $this->getDistributor();

        if (! $distributor) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'Please create your distributor profile first');
        }

        $query = Order::with(['customer', 'items'])
            ->where('distributor_id', $distributor->id);

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search != '') {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('customer', function ($q) use ($request) {
                        $q->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Owner/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }

    /**
     * Show order details
     */
    public function show(Order $order)
    {
        $distributor = $this->getDistributor();

        // Ensure distributor owns this order
        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $order->load([
            'customer',
            'items.product.images',
            'items.productVariation',
            'items.inventory.branch',
            'invoice.payments',
            'delivery.courier.user',
            'distributor',
            'productReviews.product',
        ]);

        $paymentSettlement = [
            'state' => 'unpaid',
            'label' => 'Unpaid',
            'payment_method' => $order->payment_method,
            'payment_method_label' => self::paymentMethodLabel($order->payment_method),
        ];
        if ($order->invoice && $order->invoice->payments->isNotEmpty()) {
            $hasReleased = $order->invoice->payments->contains(fn ($p) => $p->status === 'verified' && $p->escrow_status === 'released');
            $hasHeld     = $order->invoice->payments->contains(fn ($p) => $p->status === 'verified' && $p->escrow_status === 'held');
            $hasRefunded = $order->invoice->payments->contains(fn ($p) => $p->status === 'verified' && $p->escrow_status === 'refunded');

            if ($hasReleased) {
                $paymentSettlement['state'] = 'released';
                $paymentSettlement['label'] = 'Released';
            } elseif ($hasHeld) {
                $paymentSettlement['state'] = 'pending_release';
                $paymentSettlement['label'] = 'Pending Release';
            } elseif ($hasRefunded) {
                $paymentSettlement['state'] = 'refunded';
                $paymentSettlement['label'] = 'Refunded';
            } else {
                $paymentSettlement['state'] = 'pending_verification';
                $paymentSettlement['label'] = 'Pending Verification';
            }

            // Use the verified payment's method if available; fall back to order method.
            $verifiedPayment = $order->invoice->payments
                ->where('status', 'verified')
                ->sortByDesc(fn ($p) => $p->verified_at?->getTimestamp() ?? 0)
                ->first();
            $latestPayment = $order->invoice->payments->sortByDesc('id')->first();
            $methodCode = $verifiedPayment?->payment_method
                ?? $latestPayment?->payment_method
                ?? $order->payment_method;
            $paymentSettlement['payment_method'] = $methodCode;
            $paymentSettlement['payment_method_label'] = self::paymentMethodLabel($methodCode);
        }

        $conversation = $order->getOrCreateShopConversation();

        return Inertia::render('Owner/Orders/Show', [
            'order' => $order,
            'paymentSettlement' => $paymentSettlement,
            'orderMessaging' => [
                'href' => route('owner.messages.show', $conversation),
                'label' => 'Message customer',
            ],
        ]);
    }

    /**
     * Show order receipt (POS thermal style)
     */
    public function receipt(Order $order)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $order->load([
            'customer',
            'items.product',
            'distributor',
            'invoice'
        ]);

        return Inertia::render('Owner/Orders/Receipt', [
            'order' => $order
        ]);
    }

    private static function paymentMethodLabel(?string $code): string
    {
        return match ($code) {
            'bank_transfer' => 'Bank Transfer',
            'gcash'         => 'GCash',
            'paymaya'       => 'Maya',
            'paymongo'      => 'Online Checkout',
            'card'          => 'Credit / Debit Card',
            'grab_pay'      => 'GrabPay',
            'cod'           => 'Cash on Delivery',
            'cash'          => 'Cash',
            'wallet'        => 'Wallet',
            default         => ucfirst(str_replace('_', ' ', $code ?? 'Unknown')),
        };
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order, OrderPrescriptionRefundService $refundService)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            Log::warning('[OrderController] Unauthorized status update attempt', [
                'order_id' => $order->id,
                'distributor_id' => $distributor->id,
                'order_distributor_id' => $order->distributor_id,
            ]);
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected,packed,cancelled,ready_for_pickup',
            'packaging_before' => 'required_if:status,packed|image|max:5120',
            'packaging_after' => 'required_if:status,packed|image|max:5120',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if ($newStatus === 'packed' && $oldStatus !== 'approved') {
            return back()->withErrors([
                'error' => "Order must be 'approved' before it can be marked as 'packed'.",
            ]);
        }

        if ($newStatus === 'approved' && $order->prescriptionBlocksFulfillment()) {
            return back()->withErrors([
                'error' => 'You cannot approve fulfillment until the customer’s prescription is verified (uploaded and approved).',
            ]);
        }

        // Bug 14: Enforce valid state machine transitions
        $validTransitions = [
            'pending' => ['approved', 'rejected', 'cancelled'],
            'approved' => ['packed', 'ready_for_pickup', 'cancelled'],
            'packed' => [],          // Shipped/Delivered handled by Courier
            'ready_for_pickup' => [], // Customer confirms received
            'shipped' => [],          // Terminal for owner
            'delivered' => [],          // Terminal state
            'cancelled' => [],          // Terminal state
            'rejected' => [],          // Terminal state
            'discount_review' => ['approved', 'rejected', 'cancelled'],
            'prescription_review' => ['approved', 'rejected', 'cancelled'],
        ];

        if (! in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            Log::warning('[OrderController] Invalid status transition attempted', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            return back()->withErrors([
                'error' => "Cannot transition order from '{$oldStatus}' to '{$newStatus}'.",
            ]);
        }

        // Prevent invalid status transitions
        if ($oldStatus === $newStatus) {
            return back()->with('info', 'Order status is already '.$oldStatus);
        }

        // If approving order, validate stock and deduct inventory
        if ($newStatus === 'approved' && $oldStatus === 'pending') {
            // Re-validate stock availability before approval
            foreach ($order->items as $item) {
                if ($item->inventory->quantity < $item->quantity) {
                    Log::error('[OrderController] Insufficient stock for approval', [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'required' => $item->quantity,
                        'available' => $item->inventory->quantity,
                    ]);

                    return back()->withErrors([
                        'error' => "Insufficient stock for {$item->product->name}. Required: {$item->quantity}, Available: {$item->inventory->quantity}",
                    ]);
                }
            }

            // Deduct actual stock and clear reservation
            foreach ($order->items as $item) {
                if (! $item->inventory->deduct($item->quantity)) {
                    Log::error('[OrderController] Failed to deduct stock', [
                        'inventory_id' => $item->inventory_id,
                        'quantity' => $item->quantity,
                    ]);

                    return back()->withErrors(['error' => 'Failed to deduct inventory']);
                }
            }
        }

        // If rejecting/cancelling, handle inventory correctly based on prior status
        if (in_array($newStatus, ['rejected', 'cancelled'])) {
            try {
                foreach ($order->items as $item) {
                    if ($oldStatus === 'pending') {
                        // Stock was reserved but NOT physically deducted — release reservation only
                        $item->inventory->releaseReservation($item->quantity);
                    } elseif ($oldStatus === 'approved') {
                        // Stock was already physically deducted at approval — restore it
                        $item->inventory->increment('quantity', $item->quantity);
                        // Also zero out any residual reservation (should already be 0)
                        $item->inventory->update([
                            'reserved_quantity' => max(0, $item->inventory->reserved_quantity - $item->quantity),
                        ]);
                    }
                    // packed/shipped orders should not be cancellable — enforced by status machine
                }
            } catch (\Exception $e) {
                Log::error('[OrderController] Failed to restore inventory during cancellation', [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);

                return back()->withErrors(['error' => 'Failed to restore inventory: '.$e->getMessage()]);
            }
        }

        // If packed, handle photos and create delivery record
        if ($newStatus === 'packed' && $oldStatus !== 'packed') {
            if ($request->hasFile('packaging_before')) {
                $order->packaging_before_image_path = $request->file('packaging_before')->store('orders/packaging', 'public');
            }
            if ($request->hasFile('packaging_after')) {
                $order->packaging_after_image_path = $request->file('packaging_after')->store('orders/packaging', 'public');
            }

            // Capture fragile flag
            if ($request->has('is_fragile')) {
                $order->is_fragile = filter_var($request->is_fragile, FILTER_VALIDATE_BOOLEAN);
            }

            $order->packed_at = now();
            // Store status here for fresh() notification
            $order->status = 'packed';
            $order->save();

            // Notify via chat with photos
            app(OrderChatAutomationService::class)->sendPackagingPhotosMessage($order->fresh());

            $order->loadMissing([]);

            Delivery::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'tracking_number' => Delivery::generateTrackingNumber(),
                    'delivery_address' => $order->delivery_address ?? 'No address provided',
                    'courier_fee' => round((float) $order->shipping_fee * (float) config('services.shipping.courier_share_rate', 0.8), 2),
                    'courier_payout_status' => 'pending',
                    'status' => 'scheduled', // 'pending' is not a valid ENUM value — use 'scheduled' for pool
                ]
            );
        }

        // We no longer create the delivery record on 'shipped', because the Courier will be the one updating it to 'shipped' (in transit).
        // However, if the owner manually transitions it, we just let the state update.
        if ($newStatus === 'delivered') {
            $order->update(['delivered_at' => now()]);
            if ($order->delivery) {
                $order->delivery->update([
                    'status' => 'delivered',
                    'actual_delivery_at' => now(),   // Bug 17 fix: correct column name
                ]);
            }

            // No inventory change here - already deducted at approval
        }

        // Update order status
        $order->update(['status' => $newStatus]);

        if ($newStatus === 'approved' && $oldStatus === 'pending') {
            app(OrderChatAutomationService::class)->sendOrderAcceptedMessage($order->fresh());
        }

        // If owner cancels/rejects after payment, refund customer and claw back seller proceeds.
        if (in_array($newStatus, ['rejected', 'cancelled'], true)) {
            $refundService->refundAfterOrderCancellation($order->fresh(), $newStatus);
        }

        $order = $order->fresh();
        $order->loadMissing(['customer', 'distributor']);
        $shopName = $order->distributor?->company_name ?? 'the seller';

        $kindMap = [
            'approved' => 'order_accepted',
            'rejected' => 'order_rejected',
            'packed' => 'order_packed',
            'cancelled' => 'order_cancelled',
            'ready_for_pickup' => 'ready_for_pickup',
        ];

        if (isset($kindMap[$newStatus]) && $order->customer) {
            $order->customer->notify(new OrderNotification($order, $kindMap[$newStatus], [
                'shop_name' => $shopName,
            ]));
        }

        return back()->with('success', "Order status updated to {$newStatus}");
    }

    /**
     * Add order notes
     */
    public function addNote(Request $request, Order $order)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $currentNotes = $order->notes ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] ".$request->note;

        $order->update([
            'notes' => $currentNotes ? $currentNotes."\n\n".$newNote : $newNote,
        ]);

        return back()->with('success', 'Note added successfully');
    }

    /**
     * Distributor confirms they received the COD cash remittance from the courier.
     * This is the final step — it releases the courier's shipping fee payout.
     */
    public function confirmCodRemittance(Order $order)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        if ($order->payment_method !== 'cod') {
            return back()->withErrors(['error' => 'This is not a COD order.']);
        }

        $delivery = $order->delivery;

        if (! $delivery || ! $delivery->cod_collected_at) {
            return back()->withErrors(['error' => 'Courier has not yet confirmed cash collection from the customer.']);
        }

        if (! $delivery->cod_remittance_sent_at) {
            return back()->withErrors(['error' => 'Courier has not yet marked the cash as sent. Please wait for the courier to hand over the cash.']);
        }

        if ($delivery->cod_remitted_at) {
            return back()->with('info', 'COD remittance already confirmed.');
        }

        // Confirm remittance received from courier
        $delivery->update(['cod_remitted_at' => now()]);

        // Release courier payout — held until now for COD orders
        if ($delivery->courier_payout_status === 'pending' && (float) $delivery->courier_fee > 0) {
            $courierUser = $delivery->courier?->user;
            if ($courierUser && $courierUser->wallet) {
                $courierUser->wallet->credit(
                    (float) $delivery->courier_fee,
                    'delivery_fee',
                    (string) $delivery->id,
                    "Courier fee for {$delivery->tracking_number}"
                );
            }
            $delivery->update([
                'courier_payout_status' => 'paid',
                'courier_paid_at' => now(),
            ]);
        }

        // Mark order completed
        $order->update([
            'status' => 'completed',
            'received_at' => now(),
        ]);

        // Sync COD invoice and payment status now that cash is confirmed received.
        $order->loadMissing('invoice.payments');
        if ($order->invoice) {
            $order->invoice->payments()
                ->where('payment_method', 'cod')
                ->where('status', 'pending')
                ->update(['status' => 'verified', 'verified_at' => now()]);

            $order->invoice->update(['status' => 'paid']);
        }

        return back()->with('success', 'COD remittance confirmed. Courier payout released. Order is now complete.');
    }

    /**
     * Approve uploaded prescription — creates invoice so customer can pay.
     */
    public function approvePrescription(Request $request, Order $order, PrescriptionChatService $prescriptionChat)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        if ($order->prescription_status !== Order::PRESCRIPTION_PENDING_REVIEW) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Prescription is not pending review.'], 422);
            }

            return back()->withErrors(['error' => 'Prescription is not pending review.']);
        }

        $order->update([
            'prescription_status' => Order::PRESCRIPTION_APPROVED,
            'prescription_reviewed_at' => now(),
            'prescription_review_note' => null,
        ]);

        $order = $order->fresh();
        $prescriptionChat->postShopApproved($order);

        $order->loadMissing(['customer', 'distributor']);
        if ($order->customer) {
            $order->customer->notify(new OrderNotification($order, 'prescription_approved', [
                'shop_name' => $order->distributor?->company_name ?? 'the seller',
            ]));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Prescription approved.']);
        }

        return back()->with('success', 'Prescription approved. You can fulfill this order when ready.');
    }

    /**
     * Reject prescription — cancel order and release reserved stock.
     */
    public function rejectPrescription(Request $request, Order $order, OrderPrescriptionRefundService $refundService, PrescriptionChatService $prescriptionChat)
    {
        $distributor = $this->getDistributor();

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($order->prescription_status !== Order::PRESCRIPTION_PENDING_REVIEW) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Prescription is not pending review.'], 422);
            }

            return back()->withErrors(['error' => 'Prescription is not pending review.']);
        }

        $reason = (string) $request->input('reason');

        DB::transaction(function () use ($order, $request, $refundService) {
            $order->load('items.inventory');

            foreach ($order->items as $item) {
                $inv = $item->inventory;
                if (! $inv) {
                    continue;
                }

                $reserved = (int) $inv->reserved_quantity;
                $qty = (int) $item->quantity;

                // Prescription rejection should only release what is actually reserved.
                // Inventory::releaseReservation throws if requested qty > reserved qty.
                if ($reserved > 0) {
                    $inv->releaseReservation(min($qty, $reserved));
                }
            }

            $order->update([
                'prescription_status' => Order::PRESCRIPTION_REJECTED,
                'prescription_review_note' => $request->reason,
                'prescription_reviewed_at' => now(),
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $refundService->refundAfterPrescriptionRejection($order->fresh());
        });

        $order = $order->fresh();
        $prescriptionChat->postShopRejected($order, $reason);

        $order->loadMissing(['customer', 'distributor']);
        if ($order->customer) {
            $order->customer->notify(new OrderNotification($order, 'prescription_rejected', [
                'shop_name' => $order->distributor?->company_name ?? 'the seller',
                'reason' => $reason,
            ]));
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Prescription rejected.']);
        }

        return back()->with('success', 'Prescription rejected. The order was cancelled, stock reservations released, and the customer refunded where applicable.');
    }

    /**
     * Approve SC/PWD discount
     */
    public function approveDiscount(Request $request, Order $order)
    {
        $distributor = $this->getDistributor();
        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        if (!$order->needsDiscountReview()) {
            return back()->withErrors(['error' => 'Discount is not pending review.']);
        }

        $order->update([
            'discount_status' => Order::DISCOUNT_APPROVED,
            'discount_reviewed_at' => now(),
            'discount_review_note' => null,
        ]);

        $order->loadMissing(['customer', 'distributor']);
        if ($order->customer) {
            $order->customer->notify(new OrderNotification($order, 'discount_approved', [
                'shop_name' => $order->distributor?->company_name ?? 'the seller',
                'discount_type' => $order->discount_type === 'senior' ? 'Senior Citizen' : 'PWD',
            ]));
        }

        return back()->with('success', 'Discount approved. The VAT exemption and 20% discount are now valid for this order.');
    }

    /**
     * Reject SC/PWD discount - cancels order as per requirements
     */
    public function rejectDiscount(Request $request, Order $order, OrderPrescriptionRefundService $refundService)
    {
        $distributor = $this->getDistributor();
        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$order->needsDiscountReview()) {
            return back()->withErrors(['error' => 'Discount is not pending review.']);
        }

        DB::transaction(function () use ($order, $request, $refundService) {
            $order->load('items.inventory');

            foreach ($order->items as $item) {
                if ($order->status === 'pending') {
                    $item->inventory->releaseReservation($item->quantity);
                }
            }

            $order->update([
                'discount_status' => Order::DISCOUNT_REJECTED,
                'discount_review_note' => $request->reason,
                'discount_reviewed_at' => now(),
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $refundService->refundAfterOrderCancellation($order->fresh(), 'rejected');
        });

        $order->loadMissing(['customer', 'distributor']);
        if ($order->customer) {
            $order->customer->notify(new OrderNotification($order, 'discount_rejected', [
                'shop_name' => $order->distributor?->company_name ?? 'the seller',
                'reason' => $request->reason,
            ]));
        }

        return back()->with('success', 'Discount rejected and order cancelled.');
    }
}
