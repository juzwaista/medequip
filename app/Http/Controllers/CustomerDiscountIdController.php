<?php

namespace App\Http\Controllers;

use App\Models\CustomerDiscountId;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CustomerDiscountIdController extends Controller
{
    /**
     * Display all saved discount IDs
     */
    public function index()
    {
        $discountIds = auth()->user()->discountIds()->latest()->get();
        
        return Inertia::render('Customer/DiscountIds/Index', [
            'discountIds' => $discountIds,
        ]);
    }
    
    /**
     * Store a new discount ID
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'discount_type' => 'required|string|in:senior,pwd',
            'id_name' => 'required|string|max:100',
            'id_number' => 'required|string|max:50',
            'id_image' => 'required|image|max:8192',
            'is_default' => 'boolean',
        ], [
            'id_image.required' => 'The ID photo is required.',
            'id_image.image' => 'The document uploaded must be an image (PNG, JPG, etc.).',
        ]);
        
        // Handle file upload
        if ($request->hasFile('id_image')) {
            $path = $request->file('id_image')->store('discount_ids', 'public');
            $validated['id_image_path'] = $path;
            unset($validated['id_image']);
        }
        
        // If setting as default, unset other defaults
        if ($request->is_default) {
            auth()->user()->discountIds()->update(['is_default' => false]);
        }
        
        $discountId = auth()->user()->discountIds()->create($validated);
        
        return back()->with('success', 'Discount ID saved successfully!');
    }
    
    /**
     * Update an existing discount ID
     */
    public function update(Request $request, CustomerDiscountId $discountId)
    {
        // Ensure user owns this discount ID
        if ($discountId->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'discount_type' => 'required|string|in:senior,pwd',
            'id_name' => 'required|string|max:100',
            'id_number' => 'required|string|max:50',
            'id_image' => 'nullable|image|max:8192',
            'is_default' => 'boolean',
        ], [
            'id_image.image' => 'The document uploaded must be an image (PNG, JPG, etc.).',
        ]);
        
        // Handle file upload
        if ($request->hasFile('id_image')) {
            $path = $request->file('id_image')->store('discount_ids', 'public');
            $validated['id_image_path'] = $path;
            unset($validated['id_image']);
        }
        
        // If setting as default, unset other defaults
        if ($request->is_default) {
            auth()->user()->discountIds()->where('id', '!=', $discountId->id)
                ->update(['is_default' => false]);
        }
        
        $discountId->update($validated);
        
        return back()->with('success', 'Discount ID updated successfully!');
    }
    
    /**
     * Set a discount ID as default
     */
    public function setDefault(CustomerDiscountId $discountId)
    {
        // Ensure user owns this discount ID
        if ($discountId->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        // Unset all other defaults
        auth()->user()->discountIds()->update(['is_default' => false]);
        
        // Set this as default
        $discountId->update(['is_default' => true]);
        
        return back()->with('success', 'Default discount ID updated!');
    }
    
    /**
     * Delete a discount ID
     */
    public function destroy(CustomerDiscountId $discountId)
    {
        // Ensure user owns this discount ID
        if ($discountId->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        $discountId->delete();
        
        return back()->with('success', 'Discount ID deleted successfully!');
    }
}
