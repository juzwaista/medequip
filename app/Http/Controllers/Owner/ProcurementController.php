<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Mail\SupplierPurchaseOrderMail;
use App\Models\Distributor;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchaseOrder;
use App\Models\SupplierPurchaseOrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProcurementController extends Controller
{
    /**
     * Allowed status changes. "partially_received" is reached only by receiving part of a
     * delivery, never chosen directly, and completed / cancelled orders are final (moving a
     * completed PO back to "sent" and completing it again used to add its stock a second time).
     */
    private const TRANSITIONS = [
        'draft' => ['sent', 'cancelled'],
        'sent' => ['completed', 'cancelled'],
        'partially_received' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    private function distributorFor(Request $request): Distributor
    {
        $user = $request->user();
        $distributor = $user->distributor ?? ($user->role === 'staff' ? $user->employer : null);

        if (! $distributor) {
            abort(403);
        }

        return $distributor;
    }

    private function ownPurchaseOrder(Request $request, SupplierPurchaseOrder $purchaseOrder): Distributor
    {
        $distributor = $this->distributorFor($request);

        if ($purchaseOrder->distributor_id !== $distributor->id) {
            abort(403);
        }

        return $distributor;
    }

    public function index(Request $request)
    {
        $distributor = $this->distributorFor($request);

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
        $distributor = $this->distributorFor($request);

        $suppliers = Supplier::where('distributor_id', $distributor->id)->orderBy('name')->get();

        $products = Product::where('distributor_id', $distributor->id)
            ->with([
                'inventory',
                'variations' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $variations = $product->variations->map(fn ($v) => [
                    'id' => $v->id,
                    'label' => $v->display_label,
                    'stock' => (int) $product->inventory->where('product_variation_id', $v->id)->sum('quantity'),
                    'units_per_pack' => $product->linePackSize($v),
                    'unit_label' => $product->lineUnitLabel($v),
                ])->values();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit_label' => $product->unit_label ?: 'piece',
                    'units_per_pack' => $product->packSize(),
                    // Stock of the product itself; products with variations show it per variation.
                    'stock' => $variations->isEmpty()
                        ? (int) $product->inventory->whereNull('product_variation_id')->sum('quantity')
                        : null,
                    'variations' => $variations,
                ];
            });

        return Inertia::render('Owner/Procurement/CreatePO', [
            'suppliers' => $suppliers,
            'products' => $products,
            'prefill' => $request->only(['product_id', 'qty']),
        ]);
    }

    public function store(Request $request)
    {
        $distributor = $this->distributorFor($request);

        $validated = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('distributor_id', $distributor->id)],
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('distributor_id', $distributor->id)],
            'items.*.product_variation_id' => 'nullable|integer',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        // A product with variations must say which one is being ordered, and it must be one of
        // that product's own variations; a product without variations must not name one.
        $errors = [];
        foreach ($validated['items'] as $i => $item) {
            $activeVariationIds = Product::findOrFail($item['product_id'])
                ->variations()->where('is_active', true)->pluck('id')->all();
            $chosen = $item['product_variation_id'] ?? null;

            if ($activeVariationIds !== [] && ! in_array((int) $chosen, $activeVariationIds, true)) {
                $errors["items.$i.product_variation_id"] = 'Choose which option of this product you are ordering.';
            } elseif ($activeVariationIds === [] && $chosen) {
                $errors["items.$i.product_variation_id"] = 'This product has no options.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        DB::transaction(function () use ($validated, $distributor) {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += ($item['quantity_ordered'] * $item['unit_cost']);
            }

            $po = SupplierPurchaseOrder::create([
                'distributor_id' => $distributor->id,
                'supplier_id' => $validated['supplier_id'],
                'po_number' => 'PO-'.strtoupper(uniqid()),
                'status' => 'draft',
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'total_amount' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                SupplierPurchaseOrderItem::create([
                    'supplier_purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'product_variation_id' => $item['product_variation_id'] ?? null,
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                ]);
            }
        });

        return redirect()->route('owner.procurement.index')->with('success', 'Purchase Order created successfully.');
    }

    public function show(Request $request, SupplierPurchaseOrder $purchase_order)
    {
        $this->ownPurchaseOrder($request, $purchase_order);

        $purchase_order->load(['supplier', 'items.product', 'items.productVariation']);

        $purchase_order->items->each(function ($item) {
            $item->setAttribute('unit_label', $item->product->lineUnitLabel($item->productVariation));
            $item->setAttribute('units_per_pack', $item->product->linePackSize($item->productVariation));
        });

        return Inertia::render('Owner/Procurement/ShowPO', [
            'po' => $purchase_order,
        ]);
    }

    public function updateStatus(Request $request, SupplierPurchaseOrder $purchase_order)
    {
        $distributor = $this->ownPurchaseOrder($request, $purchase_order);

        $validated = $request->validate([
            'status' => 'required|in:sent,completed,cancelled',
        ]);
        $newStatus = $validated['status'];

        $error = null;
        DB::transaction(function () use ($purchase_order, $newStatus, &$error) {
            $po = SupplierPurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($newStatus, self::TRANSITIONS[$po->status] ?? [], true)) {
                $error = "A {$po->status} purchase order can't be changed to {$newStatus}.";

                return;
            }

            if ($newStatus === 'completed') {
                // Receive whatever hasn't arrived yet in one go.
                $po->load('items');
                $this->applyReceipt($po, $po->items->mapWithKeys(
                    fn ($item) => [$item->id => $item->quantity_ordered - $item->quantity_received]
                )->all());
                $po->update(['status' => 'completed']);

                return;
            }

            $po->update(['status' => $newStatus]);
        });

        if ($error) {
            return back()->with('error', $error);
        }

        $flash = ['success' => 'Purchase Order status updated.'];

        // Email the supplier after the status change is saved, so a mail/PDF problem can never undo it.
        if ($newStatus === 'sent') {
            $purchase_order->refresh()->load(['supplier', 'items.product', 'items.productVariation']);
            if ($purchase_order->supplier->email && ! $this->emailPurchaseOrder($purchase_order, $distributor)) {
                $flash = ['warning' => 'Purchase Order marked as sent, but the email to the supplier could not be delivered. Please send it to them another way.'];
            }
        }

        return back()->with($flash);
    }

    /**
     * Record a delivery (the whole order or just part of it) and add it to stock.
     */
    public function receive(Request $request, SupplierPurchaseOrder $purchase_order)
    {
        $this->ownPurchaseOrder($request, $purchase_order);

        $validated = $request->validate([
            'quantities' => 'required|array',
            'quantities.*' => 'nullable|integer|min:0|max:1000000',
        ]);

        $error = null;
        DB::transaction(function () use ($purchase_order, $validated, &$error) {
            $po = SupplierPurchaseOrder::whereKey($purchase_order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($po->status, ['sent', 'partially_received'], true)) {
                $error = 'Only a sent purchase order can receive stock.';

                return;
            }

            $po->load('items');
            $received = $this->applyReceipt($po, $validated['quantities']);

            if ($received === 0) {
                $error = 'Enter how many units arrived for at least one item.';
            }
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Delivery recorded and stock updated.');
    }

    /**
     * Add the given quantities (item id => units received now, capped at what is still
     * outstanding) to stock and move the PO to partially_received / completed.
     *
     * @param  array<int|string, int|string|null>  $quantities
     * @return int total units received
     */
    private function applyReceipt(SupplierPurchaseOrder $po, array $quantities): int
    {
        $total = 0;

        foreach ($po->items as $item) {
            $remaining = $item->quantity_ordered - $item->quantity_received;
            $qty = min($remaining, max(0, (int) ($quantities[$item->id] ?? 0)));

            if ($qty <= 0) {
                continue;
            }

            $this->restock($item, $qty);
            $item->increment('quantity_received', $qty);
            $total += $qty;
        }

        $po->load('items');
        $complete = $po->items->every(fn ($i) => $i->quantity_received >= $i->quantity_ordered);
        $any = $po->items->contains(fn ($i) => $i->quantity_received > 0);

        if ($complete) {
            $po->update(['status' => 'completed']);
        } elseif ($any) {
            $po->update(['status' => 'partially_received']);
        }

        return $total;
    }

    /**
     * Add units to the inventory row for this exact product + variation on the main location.
     */
    private function restock(SupplierPurchaseOrderItem $item, int $quantity): void
    {
        $inventory = Inventory::where('product_id', $item->product_id)
            ->where('product_variation_id', $item->product_variation_id)
            ->whereNull('branch_id')
            ->lockForUpdate()
            ->first();

        if ($inventory) {
            $inventory->increment('quantity', $quantity);

            return;
        }

        Inventory::create([
            'product_id' => $item->product_id,
            'product_variation_id' => $item->product_variation_id,
            'branch_id' => null,
            'quantity' => $quantity,
            'reorder_level' => Inventory::where('product_id', $item->product_id)->value('reorder_level') ?? 10,
        ]);
    }

    private function emailPurchaseOrder(SupplierPurchaseOrder $po, Distributor $distributor): bool
    {
        try {
            $pdf = Pdf::loadView('pdf.purchase_order', ['po' => $po, 'distributor' => $distributor]);
            Mail::to($po->supplier->email)->send(new SupplierPurchaseOrderMail($po, $pdf->output()));

            return true;
        } catch (\Throwable $e) {
            Log::error('[Procurement] Failed to email purchase order', [
                'po_id' => $po->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
