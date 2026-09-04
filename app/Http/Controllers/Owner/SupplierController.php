<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        
        if (!$distributor) {
            abort(403);
        }

        $suppliers = Supplier::where('distributor_id', $distributor->id)
            ->latest()
            ->paginate(20);

        return Inertia::render('Owner/Suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        
        if (!$distributor) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['distributor_id'] = $distributor->id;

        Supplier::create($validated);

        return back()->with('success', 'Supplier created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        
        if (!$distributor || $supplier->distributor_id !== $distributor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Supplier $supplier)
    {
        $distributor = $request->user()->distributor ?? ($request->user()->role === 'staff' ? $request->user()->distributorStaff : null);
        
        if (!$distributor || $supplier->distributor_id !== $distributor->id) {
            abort(403);
        }

        $supplier->delete();

        return back()->with('success', 'Supplier deleted successfully.');
    }
}
