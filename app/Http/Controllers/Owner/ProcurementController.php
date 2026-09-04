<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierPurchaseOrder;
use App\Models\SupplierPurchaseOrderItem;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SupplierPurchaseOrderMail;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class ProcurementController extends Controller
{
    public function index(Request $request)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        if (!$distributor) {
            abort(403);
        }

        $purchaseOrders = SupplierPurchaseOrder::with(['supplier'])
            ->where('distributor_id', $distributor->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Owner/Procurement/Index', [
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    public function create(Request $request)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        if (!$distributor) {
            abort(403);
        }

        $suppliers = Supplier::where('distributor_id', $distributor->id)->get();
        $products = Product::where('distributor_id', $distributor->id)->get();

        return Inertia::render('Owner/Procurement/CreatePO', [
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        if (!$distributor) {
            abort(403);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated, $distributor) {
            $po_number = 'PO-' . strtoupper(uniqid());
            
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += ($item['quantity_ordered'] * $item['unit_cost']);
            }

            $po = SupplierPurchaseOrder::create([
                'distributor_id' => $distributor->id,
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $po_number,
                'status' => 'draft',
                'expected_delivery_date' => $validated['expected_delivery_date'],
                'total_amount' => $total,
                'notes' => $validated['notes'],
            ]);

            foreach ($validated['items'] as $item) {
                SupplierPurchaseOrderItem::create([
                    'supplier_purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }
        });

        return redirect()->route('owner.procurement.index')->with('success', 'Purchase Order created successfully.');
    }

    public function show(Request $request, SupplierPurchaseOrder $purchase_order)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        if (!$distributor || $purchase_order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $purchase_order->load(['supplier', 'items.product']);

        return Inertia::render('Owner/Procurement/ShowPO', [
            'po' => $purchase_order,
        ]);
    }

    public function updateStatus(Request $request, SupplierPurchaseOrder $purchase_order)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        if (!$distributor || $purchase_order->distributor_id !== $distributor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,partially_received,completed,cancelled'
        ]);

        $oldStatus = $purchase_order->status;
        $newStatus = $validated['status'];

        DB::transaction(function () use ($purchase_order, $newStatus, $oldStatus, $distributor) {
            $purchase_order->update(['status' => $newStatus]);

            // If transitioning to completed, automatically restock inventory
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $purchase_order->load('items');
                foreach ($purchase_order->items as $item) {
                    // Update the received quantity to match ordered if it wasn't partially received
                    if ($item->quantity_received < $item->quantity_ordered) {
                        $item->update(['quantity_received' => $item->quantity_ordered]);
                    }

                    $inventory = Inventory::where('product_id', $item->product_id)
                        ->where('branch_id', null) // Assuming main distributor branch
                        ->first();
                        
                    if ($inventory) {
                        $inventory->increment('quantity', $item->quantity_received);
                    } else {
                        // Create inventory if it doesn't exist
                        Inventory::create([
                            'product_id' => $item->product_id,
                            'branch_id' => null,
                            'quantity' => $item->quantity_received,
                        ]);
                    }
                }
            }

            // If transitioning to sent, email the supplier
            if ($newStatus === 'sent' && $oldStatus === 'draft') {
                $purchase_order->load(['supplier', 'items.product']);
                if ($purchase_order->supplier->email) {
                    try {
                        // Assuming PDF generation
                        $pdf = Pdf::loadView('pdf.purchase_order', ['po' => $purchase_order, 'distributor' => $distributor]);
                        Mail::to($purchase_order->supplier->email)->send(new SupplierPurchaseOrderMail($purchase_order, $pdf->output()));
                    } catch (\Exception $e) {
                        \Log::error('Failed to send PO email: ' . $e->getMessage());
                    }
                }
            }
        });

        return back()->with('success', 'Purchase Order status updated.');
    }
}
