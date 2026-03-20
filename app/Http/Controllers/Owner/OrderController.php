<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Display orders list
     */
    public function index(Request $request)
    {
        $distributor = auth()->user()->distributor;

        if (!$distributor) {
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
                $q->where('order_number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('customer', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
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
        $distributor = auth()->user()->distributor;

        // Ensure distributor owns this order
        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $order->load([
            'customer',
            'items.product.images',
            'items.inventory.branch',
            'invoice.payments',
            'delivery'
        ]);

        return Inertia::render('Owner/Orders/Show', [
            'order' => $order,
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $distributor = auth()->user()->distributor;

        if ($order->distributor_id !== $distributor->id) {
            \Log::warning('[OrderController] Unauthorized status update attempt', [
                'order_id' => $order->id,
                'distributor_id' => $distributor->id,
                'order_distributor_id' => $order->distributor_id
            ]);
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,approved,rejected,packed,cancelled',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Bug 14: Enforce valid state machine transitions
        $validTransitions = [
            'pending'   => ['approved', 'rejected', 'cancelled'],
            'approved'  => ['packed', 'cancelled'],
            'packed'    => [],          // Shipped/Delivered handled by Courier
            'shipped'   => [],          // Terminal for owner
            'delivered' => [],          // Terminal state
            'cancelled' => [],          // Terminal state
            'rejected'  => [],          // Terminal state
        ];

        if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
            \Log::warning('[OrderController] Invalid status transition attempted', [
                'order_id'   => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
            return back()->withErrors([
                'error' => "Cannot transition order from '{$oldStatus}' to '{$newStatus}'.",
            ]);
        }

        \Log::info('[OrderController] Status update initiated', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'old_status'   => $oldStatus,
            'new_status'   => $newStatus
        ]);

        // Prevent invalid status transitions
        if ($oldStatus === $newStatus) {
            return back()->with('info', 'Order status is already ' . $oldStatus);
        }

        // If approving order, validate stock and deduct inventory
        if ($newStatus === 'approved' && $oldStatus === 'pending') {
            \Log::info('[OrderController] Approving order, validating stock availability');
            
            // Re-validate stock availability before approval
            foreach ($order->items as $item) {
                if ($item->inventory->quantity < $item->quantity) {
                    \Log::error('[OrderController] Insufficient stock for approval', [
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'required' => $item->quantity,
                        'available' => $item->inventory->quantity
                    ]);
                    
                    return back()->withErrors([
                        'error' => "Insufficient stock for {$item->product->name}. Required: {$item->quantity}, Available: {$item->inventory->quantity}"
                    ]);
                }
            }

            // Deduct actual stock and clear reservation
            foreach ($order->items as $item) {
                if (!$item->inventory->deduct($item->quantity)) {
                    \Log::error('[OrderController] Failed to deduct stock', [
                        'inventory_id' => $item->inventory_id,
                        'quantity' => $item->quantity
                    ]);
                    return back()->withErrors(['error' => 'Failed to deduct inventory']);
                }
            }

            \Log::info('[OrderController] Order approved, stock deducted', [
                'order_id' => $order->id
            ]);
        }

        // If rejecting/cancelling, handle inventory correctly based on prior status
        if (in_array($newStatus, ['rejected', 'cancelled'])) {
            \Log::info('[OrderController] Handling inventory for cancellation/rejection', [
                'order_id'   => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

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
                        \Log::info('[OrderController] Restored deducted stock for approved order', [
                            'inventory_id' => $item->inventory->id,
                            'restored_qty' => $item->quantity,
                        ]);
                    }
                    // packed/shipped orders should not be cancellable — enforced by status machine
                }
                \Log::info('[OrderController] Inventory handled successfully for cancellation');
            } catch (\Exception $e) {
                \Log::error('[OrderController] Failed to restore inventory during cancellation', [
                    'error'    => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
                return back()->withErrors(['error' => 'Failed to restore inventory: ' . $e->getMessage()]);
            }
        }

        // If packed, create delivery record for the Courier Dispatch pool
        if ($newStatus === 'packed' && $oldStatus !== 'packed') {
            \Log::info('[OrderController] Creating delivery record for dispatch pool');

            $order->loadMissing([]);

            Delivery::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'tracking_number'  => Delivery::generateTrackingNumber(),
                    'delivery_address' => $order->delivery_address ?? 'No address provided',
                    'status'           => 'pending',
                ]
            );
        }

        // We no longer create the delivery record on 'shipped', because the Courier will be the one updating it to 'shipped' (in transit).
        // However, if the owner manually transitions it, we just let the state update.
        if ($newStatus === 'delivered') {
            \Log::info('[OrderController] Marking order as delivered');

            $order->update(['delivered_at' => now()]);
            if ($order->delivery) {
                $order->delivery->update([
                    'status'             => 'delivered',
                    'actual_delivery_at' => now(),   // Bug 17 fix: correct column name
                ]);
            }

            // No inventory change here - already deducted at approval
            \Log::info('[OrderController] Order delivered successfully', [
                'order_id'     => $order->id,
                'delivered_at' => now()
            ]);
        }

        // Update order status
        $order->update(['status' => $newStatus]);

        \Log::info('[OrderController] Order status updated successfully', [
            'order_id' => $order->id,
            'final_status' => $newStatus
        ]);

        return back()->with('success', "Order status updated to {$newStatus}");
    }

    /**
     * Add order notes
     */
    public function addNote(Request $request, Order $order)
    {
        $distributor = auth()->user()->distributor;

        if ($order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $currentNotes = $order->notes ?? '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] " . $request->note;

        $order->update([
            'notes' => $currentNotes ? $currentNotes . "\n\n" . $newNote : $newNote,
        ]);

        return back()->with('success', 'Note added successfully');
    }
}
