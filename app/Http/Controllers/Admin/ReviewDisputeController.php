<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewDisputeController extends Controller
{
    /**
     * List all review disputes.
     */
    public function index(Request $request)
    {
        $query = ProductReview::with(['user', 'product', 'order', 'order.distributor'])
            ->where('dispute_status', '!=', 'none');

        if ($request->filled('status')) {
            $query->where('dispute_status', $request->status);
        }

        $disputes = $query->orderByDesc('updated_at')->paginate(15);

        return Inertia::render('Admin/Reviews/Disputes', [
            'disputes' => $disputes,
            'filters' => $request->only('status'),
        ]);
    }

    /**
     * Resolve a dispute.
     */
    public function resolve(Request $request, ProductReview $productReview)
    {
        $request->validate([
            'resolution' => 'required|in:upheld,rejected',
            'should_hide' => 'required|boolean',
            'admin_note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $productReview->dispute_status;
        $newStatus = $request->resolution === 'upheld' ? 'resolved' : 'rejected';

        $productReview->update([
            'dispute_status' => $newStatus,
            'is_hidden' => $request->should_hide,
            'dispute_resolved_at' => now(),
        ]);

        // Audit Log
        AuditLog::log('review_dispute_resolved', $productReview, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'resolution' => $request->resolution,
            'should_hide' => $request->should_hide,
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'Dispute resolved successfully. Actions have been logged.');
    }
}
