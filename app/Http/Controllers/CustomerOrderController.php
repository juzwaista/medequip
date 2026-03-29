<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CustomerOrderController extends Controller
{
    /**
     * Display customer's orders with enhanced filtering
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        Log::info('[CustomerOrderController] Orders page accessed', [
            'user_id' => $user->id,
            'filters' => $request->only(['status', 'search', 'date_from', 'date_to'])
        ]);

        $query = Order::with(['distributor', 'items.product', 'invoice.payments'])
            ->where('customer_id', $user->id);

        // Search by order number or product name
        if ($request->search) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('items.product', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Date range filtering
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Add product preview images to each order
        $orders->getCollection()->transform(function ($order) {
            $order->preview_images = $order->items->take(3)->map(function($item) {
                return $item->product->image_path;
            })->filter()->values();
            
            $order->remaining_items = max(0, $order->items->count() - 3);
            $order->can_pay_now = $this->canPayNow($order);
            
            return $order;
        });

        Log::info('[CustomerOrderController] Orders retrieved', [
            'count' => $orders->total(),
            'search_applied' => !empty($request->search)
        ]);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'status' => $request->status ?? '',
                'search' => $request->search ?? '',
                'date_from' => $request->date_from ?? '',
                'date_to' => $request->date_to ?? '',
            ],
        ]);
    }

    /**
     * Show order details with full product information
     */
    public function show(Order $order)
    {
        $user = auth()->user();
        
        // Ensure user owns this order
        if ($order->customer_id !== $user->id) {
            Log::warning('[CustomerOrderController] Unauthorized order access attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id
            ]);
            abort(403, 'You do not have permission to view this order.');
        }

        Log::info('[CustomerOrderController] Order details viewed', [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'order_number' => $order->order_number
        ]);

        $order->load([
            'items.product',
            'items.productVariation',
            'items.inventory.branch',
            'distributor',
            'invoice.payments',
            'delivery.courier.user',
        ]);
        $order->can_pay_now = $this->canPayNow($order);
        $order->customer_payment_status = $this->getCustomerPaymentStatus($order);

        return Inertia::render('Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Determine if customer can retry payment for this order.
     */
    private function canPayNow(Order $order): bool
    {
        if (in_array($order->status, ['cancelled', 'rejected'], true)) {
            return false;
        }

        $invoice = $order->invoice;
        if (! $invoice) {
            return false;
        }

        if ($invoice->status === 'paid') {
            return false;
        }

        $payments = $invoice->payments ?? collect();

        $hasVerifiedPayment = $payments->contains(fn($payment) => $payment->status === 'verified');
        if ($hasVerifiedPayment) {
            return false;
        }

        $hasActiveCheckoutSession = $payments->contains(
            fn($payment) => in_array($payment->paymongo_status, ['active', 'processing'], true)
        );

        if ($hasActiveCheckoutSession) {
            return false;
        }

        return true;
    }

    /**
     * Customer-facing payment status derived from payment + escrow states.
     */
    private function getCustomerPaymentStatus(Order $order): array
    {
        $invoice = $order->invoice;
        if (! $invoice) {
            if ($order->prescription_status === Order::PRESCRIPTION_AWAITING_UPLOAD) {
                return ['state' => 'rx_upload', 'label' => 'Prescription required'];
            }
            if ($order->prescription_status === Order::PRESCRIPTION_PENDING_REVIEW) {
                return ['state' => 'rx_review', 'label' => 'Prescription under review'];
            }
            if ($order->prescription_status === Order::PRESCRIPTION_REJECTED) {
                return ['state' => 'rx_rejected', 'label' => 'Prescription rejected'];
            }

            return ['state' => 'unpaid', 'label' => 'Unpaid'];
        }

        $payments = $invoice->payments ?? collect();

        $hasVerified = $payments->contains(fn($payment) => $payment->status === 'verified');
        if ($hasVerified) {
            return ['state' => 'paid', 'label' => 'Paid'];
        }

        $hasActive = $payments->contains(
            fn($payment) => in_array($payment->paymongo_status, ['active', 'processing'], true)
        );
        if ($hasActive) {
            return ['state' => 'pending_verification', 'label' => 'Pending Verification'];
        }

        $hasRejected = $payments->contains(fn($payment) => $payment->status === 'rejected');
        if ($hasRejected) {
            return ['state' => 'payment_failed', 'label' => 'Payment Failed'];
        }

        return ['state' => 'unpaid', 'label' => 'Unpaid'];
    }

    /**
     * Cancel an order (only if pending or approved)
     */
    public function cancel(Order $order, \App\Services\OrderPrescriptionRefundService $refundService)
    {
        $user = auth()->user();
        
        // Ensure user owns this order
        if ($order->customer_id !== $user->id) {
            Log::warning('[CustomerOrderController] Unauthorized cancel attempt', [
                'user_id' => $user->id,
                'order_id' => $order->id
            ]);
            abort(403, 'You do not have permission to cancel this order.');
        }

        if (!in_array($order->status, ['pending', 'approved'])) {
            Log::info('[CustomerOrderController] Cancel failed - invalid status', [
                'order_id' => $order->id,
                'status' => $order->status
            ]);
            return back()->with('error', 'Cannot cancel order in current status: ' . $order->status);
        }

        try {
            // Bug 12 fix: differentiate stock handling based on prior status
            foreach ($order->items as $item) {
                if ($order->status === 'pending') {
                    // Stock was reserved but NOT physically deducted — release reservation
                    $item->inventory->releaseReservation($item->quantity);
                } elseif ($order->status === 'approved') {
                    // Stock was physically deducted at approval — restore it
                    $item->inventory->increment('quantity', $item->quantity);
                    $item->inventory->update([
                        'reserved_quantity' => max(0, $item->inventory->reserved_quantity - $item->quantity),
                    ]);
                }
            }

            $order->update([
                'status'       => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Fix: Trigger refund if the order has been paid in advance
            $refundService->refundAfterOrderCancellation($order->fresh(), 'cancelled');

            Log::info('[CustomerOrderController] Order cancelled successfully', [
                'user_id'      => $user->id,
                'order_id'     => $order->id,
                'order_number' => $order->order_number,
                'prior_status' => $order->getOriginal('status'),
            ]);

            return back()->with('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            Log::error('[CustomerOrderController] Order cancellation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to cancel order. Please try again.');
        }
    }
}
