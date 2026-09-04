<?php

namespace App\Http\Controllers;

use App\Models\SavedPurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SavedPurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Customer/PurchaseOrders/Index', [
            'savedPurchaseOrders' => $request->user()
                ->savedPurchaseOrders()
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'                => 'required|string|max:100',
            'company_name'         => 'required|string|max:255',
            'authorized_signatory' => 'nullable|string|max:255',
            'contact_number'       => 'nullable|string|max:20',
            'billing_address'      => 'nullable|string|max:500',
            'tin'                  => 'nullable|string|max:50',
            'is_default'           => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->savedPurchaseOrders()->update(['is_default' => false]);
        }

        $po = $request->user()->savedPurchaseOrders()->create($validated);

        return back()->with('success', 'Purchase order profile saved.');
    }

    public function update(Request $request, SavedPurchaseOrder $savedPurchaseOrder)
    {
        $this->authorize('update', $savedPurchaseOrder);

        $validated = $request->validate([
            'label'                => 'required|string|max:100',
            'company_name'         => 'required|string|max:255',
            'authorized_signatory' => 'nullable|string|max:255',
            'contact_number'       => 'nullable|string|max:20',
            'billing_address'      => 'nullable|string|max:500',
            'tin'                  => 'nullable|string|max:50',
            'is_default'           => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            $request->user()->savedPurchaseOrders()->where('id', '!=', $savedPurchaseOrder->id)->update(['is_default' => false]);
        }

        $savedPurchaseOrder->update($validated);

        return back()->with('success', 'Purchase order profile updated.');
    }

    public function destroy(SavedPurchaseOrder $savedPurchaseOrder)
    {
        $this->authorize('delete', $savedPurchaseOrder);
        $savedPurchaseOrder->delete();

        return back()->with('success', 'Purchase order profile deleted.');
    }

    /**
     * Simple ownership check — gates the controller actions without a dedicated Policy.
     */
    public function authorize(string $ability, $model): void
    {
        if ($model->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
