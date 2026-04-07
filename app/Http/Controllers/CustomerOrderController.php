<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReview;
use App\Models\Order;
use App\Models\ProductReview;
use App\Notifications\OrderNotification;
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

        $query = Order::with(['distributor', 'items.product', 'invoice.payments'])
            ->where('customer_id', $user->id);

        // Quick mobile buckets (take precedence over single status filter)
        $bucket = $request->input('bucket');
        if (in_array($bucket, ['pay', 'ship', 'receive'], true)) {
            match ($bucket) {
                'pay' => $query->whereNotIn('status', ['cancelled', 'rejected'])
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereHas('invoice', fn ($iq) => $iq->where('status', '!=', 'paid'))
                                ->whereDoesntHave('invoice.payments', function ($q3) {
                                    $q3->where(function ($inner) {
                                        $inner->where('status', 'verified')
                                            ->orWhereIn('paymongo_status', ['active', 'processing']);
                                    });
                                });
                        })->orWhere(function ($q2) {
                            $q2->whereDoesntHave('invoice')
                                ->where('payment_method', 'paymongo');
                        });
                    }),
                // Confirmed by seller, not yet with courier (excludes pending = not yet accepted)
                'ship' => $query->whereIn('status', ['approved', 'packed']),
                // Courier has the order (excludes packed = still with seller)
                'receive' => $query->whereIn('status', ['shipped', 'delivered']),
            };
        } elseif ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Search by order number or product name
        if ($request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('items.product', function ($subQ) use ($searchTerm) {
                        $subQ->where('name', 'like', "%{$searchTerm}%");
                    });
            });
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
            $order->preview_images = $order->items->take(3)->map(function ($item) {
                return $item->product->image_path;
            })->filter()->values();

            $order->remaining_items = max(0, $order->items->count() - 3);
            $order->can_pay_now = $this->canPayNow($order);

            return $order;
        });

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'bucket' => $request->input('bucket', ''),
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
                'order_id' => $order->id,
            ]);
            abort(403, 'You do not have permission to view this order.');
        }

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
        $order->setAttribute('invoice_payment_display', $this->resolveInvoicePaymentMethodDisplay($order));

        $reviewEligible = filled($order->received_at);
        $existingProductReviews = ProductReview::query()
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('product_id');
        $deliveryReview = DeliveryReview::query()->where('order_id', $order->id)->first();

        $reviewState = [
            'eligible' => $reviewEligible,
            'product_rows' => $order->items->map(function ($item) use ($existingProductReviews) {
                $pr = $existingProductReviews->get($item->product_id);

                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product?->name ?? 'Product',
                    'reviewed' => $pr !== null,
                    'stars' => $pr?->stars,
                    'body' => $pr?->body,
                ];
            })->values()->all(),
            'delivery' => [
                'eligible' => $reviewEligible && $order->delivery && ! $deliveryReview,
                'submitted' => $deliveryReview !== null,
                'stars' => $deliveryReview?->stars,
                'body' => $deliveryReview?->body,
                'courier_label' => $order->delivery?->courier?->user?->name
                    ?? $order->delivery?->driver_name
                    ?? 'Courier',
            ],
        ];

        $conversation = $order->getShopConversationForOrder();
        $firstProductId = $order->items->first()?->product_id;
        $orderMessaging = $conversation !== null
            ? [
                'href' => route('messages.show', $conversation),
                'label' => 'Message seller',
            ]
            : [
                'href' => route('messages.start', array_filter([
                    'distributor_id' => $order->distributor_id,
                    'product_id' => $firstProductId,
                ], fn ($v) => $v !== null)),
                'label' => 'Message seller',
            ];

        return Inertia::render('Orders/Show', [
            'order' => $order,
            'orderMessaging' => $orderMessaging,
            'reviewState' => $reviewState,
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

        if (! $order->hasOnlinePayment()) {
            return false;
        }

        $invoice = $order->invoice;
        if (! $invoice) {
            return $order->payment_method === 'paymongo';
        }

        if ($invoice->status === 'paid') {
            return false;
        }

        $payments = $invoice->payments ?? collect();

        $hasVerifiedPayment = $payments->contains(fn ($payment) => $payment->status === 'verified');
        if ($hasVerifiedPayment) {
            return false;
        }

        // We relax this to allow retrying even if a previous session exists but didn't verify.
        // This fixes the "missing button" after clicking browser Back button.
        /*
        $hasActiveCheckoutSession = $payments->contains(
            fn ($payment) => in_array($payment->paymongo_status, ['active', 'processing'], true)
        );

        if ($hasActiveCheckoutSession) {
            return false;
        }
        */

        return true;
    }

    /**
     * Customer-facing payment status derived from payment and platform hold states.
     */
    private function getCustomerPaymentStatus(Order $order): array
    {
        // COD: payment is collected by courier — show a clear status
        if ($order->payment_method === 'cod') {
            $invoice = $order->invoice;
            if ($invoice && $invoice->status === 'paid') {
                return ['state' => 'paid', 'label' => 'Paid (Cash)'];
            }
            if ($invoice && $invoice->status === 'cancelled') {
                return ['state' => 'cancelled', 'label' => 'Cancelled'];
            }
            return ['state' => 'cod_pending', 'label' => 'Cash on Delivery'];
        }

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

        if ($invoice->status === 'cancelled') {
            return ['state' => 'cancelled', 'label' => 'Cancelled'];
        }

        $payments = $invoice->payments ?? collect();

        $hasVerified = $payments->contains(fn ($payment) => $payment->status === 'verified');
        if ($hasVerified) {
            return ['state' => 'paid', 'label' => 'Paid'];
        }

        $hasActive = $payments->contains(
            fn ($payment) => in_array($payment->paymongo_status, ['active', 'processing'], true)
        );
        if ($hasActive) {
            return ['state' => 'pending_verification', 'label' => 'Pending Verification'];
        }

        $hasRejected = $payments->contains(fn ($payment) => $payment->status === 'rejected');
        if ($hasRejected) {
            return ['state' => 'payment_failed', 'label' => 'Payment Failed'];
        }

        return ['state' => 'unpaid', 'label' => 'Unpaid'];
    }

    /**
     * Human-readable payment method for the invoice (verified payment first, else latest attempt, else order method).
     *
     * @return array{code: string, label: string}|null
     */
    private function resolveInvoicePaymentMethodDisplay(Order $order): ?array
    {
        $code = null;
        $invoice = $order->invoice;
        if ($invoice && $invoice->relationLoaded('payments')) {
            $payments = $invoice->payments;
            $verified = $payments
                ->where('status', 'verified')
                ->sortByDesc(fn ($p) => $p->verified_at?->getTimestamp() ?? 0)
                ->first();
            if ($verified && filled($verified->payment_method)) {
                $code = (string) $verified->payment_method;
            } elseif ($payments->isNotEmpty()) {
                $latest = $payments->sortByDesc('id')->first();
                if ($latest && filled($latest->payment_method)) {
                    $code = (string) $latest->payment_method;
                }
            }
        }

        if (! $code && filled($order->payment_method)) {
            $code = (string) $order->payment_method;
        }

        if (! $code) {
            return null;
        }

        return [
            'code' => $code,
            'label' => self::paymentMethodCustomerLabel($code),
        ];
    }

    private static function paymentMethodCustomerLabel(string $code): string
    {
        return match ($code) {
            'bank_transfer' => 'Bank transfer',
            'gcash' => 'GCash',
            'paymaya' => 'Maya',
            'paymongo' => 'Online checkout',
            'card' => 'Card',
            'grab_pay' => 'GrabPay',
            'cod' => 'Cash on delivery',
            'cash' => 'Cash',
            'wallet' => 'Wallet',
            default => ucfirst(str_replace('_', ' ', $code)),
        };
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
                'order_id' => $order->id,
            ]);
            abort(403, 'You do not have permission to cancel this order.');
        }

        if (! in_array($order->status, ['pending', 'approved'])) {
            return back()->with('error', 'Cannot cancel order in current status: '.$order->status);
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
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $refundService->refundAfterOrderCancellation($order->fresh(), 'cancelled');

            $order->loadMissing('distributor.user');
            if ($order->distributor?->user) {
                $order->distributor->user->notify(new OrderNotification($order, 'order_cancelled'));
            }

            return back()->with('success', 'Order cancelled successfully');
        } catch (\Exception $e) {
            Log::error('[CustomerOrderController] Order cancellation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel order. Please try again.');
        }
    }
}
