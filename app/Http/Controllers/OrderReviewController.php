<?php

namespace App\Http\Controllers;

use App\Models\DeliveryReview;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReviewController extends Controller
{
    public function storeProductReviews(Request $request, Order $order)
    {
        if ($order->customer_id !== $request->user()->id) {
            abort(403);
        }

        if (! filled($order->received_at)) {
            throw ValidationException::withMessages([
                'reviews' => ['Confirm that you received your order before leaving product reviews.'],
            ]);
        }

        $validated = $request->validate([
            'reviews' => ['required', 'array', 'min:1'],
            'reviews.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'reviews.*.stars' => ['required', 'integer', 'min:1', 'max:5'],
            'reviews.*.body' => ['nullable', 'string', 'max:2000'],
        ]);

        $allowedIds = $order->items()->pluck('product_id')->unique()->all();

        foreach ($validated['reviews'] as $row) {
            if (! in_array((int) $row['product_id'], array_map('intval', $allowedIds), true)) {
                throw ValidationException::withMessages([
                    'reviews' => ['One or more products are not part of this order.'],
                ]);
            }
        }

        DB::transaction(function () use ($validated, $order, $request) {
            foreach ($validated['reviews'] as $row) {
                ProductReview::query()->updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'order_id' => $order->id,
                        'product_id' => $row['product_id'],
                    ],
                    [
                        'stars' => $row['stars'],
                        'body' => $row['body'] ?? null,
                    ]
                );
            }
        });

        return back()->with('success', 'Thanks — your product ratings were saved.');
    }

    public function storeDeliveryReview(Request $request, Order $order)
    {
        if ($order->customer_id !== $request->user()->id) {
            abort(403);
        }

        if (! filled($order->received_at)) {
            throw ValidationException::withMessages([
                'delivery' => ['Confirm that you received your order before rating delivery.'],
            ]);
        }

        $order->loadMissing('delivery.courier');

        if (! $order->delivery) {
            throw ValidationException::withMessages([
                'delivery' => ['This order has no delivery record to rate.'],
            ]);
        }

        if (DeliveryReview::query()->where('order_id', $order->id)->exists()) {
            throw ValidationException::withMessages([
                'delivery' => ['You already submitted a delivery rating for this order.'],
            ]);
        }

        $validated = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        DeliveryReview::query()->create([
            'user_id' => $request->user()->id,
            'order_id' => $order->id,
            'delivery_id' => $order->delivery->id,
            'courier_id' => $order->delivery->courier_id,
            'stars' => $validated['stars'],
            'body' => $validated['body'] ?? null,
        ]);

        return back()->with('success', 'Thanks — your delivery rating was saved.');
    }
}
