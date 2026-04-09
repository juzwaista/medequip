<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReviewDisputeController extends Controller
{
    /**
     * Submit a dispute for a product review.
     */
    public function store(Request $request, ProductReview $productReview)
    {
        $distributor = $this->getDistributor();
        
        // Ensure the review belongs to an order from this distributor
        if ($productReview->order->distributor_id !== $distributor->id) {
            abort(403);
        }

        if ($productReview->dispute_status !== 'none') {
            return back()->with('info', 'This review is already being disputed or resolved.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $productReview->update([
            'dispute_status' => 'pending',
            'dispute_reason' => $request->reason,
        ]);

        Log::info('[ReviewDispute] Dispute submitted by distributor', [
            'review_id' => $productReview->id,
            'distributor_id' => $distributor->id,
            'order_id' => $productReview->order_id,
        ]);

        return back()->with('success', 'Review dispute submitted. Our admin team will review it alongside your packaging evidence.');
    }
}
