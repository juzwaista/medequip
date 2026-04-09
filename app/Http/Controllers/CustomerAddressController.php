<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class CustomerAddressController extends Controller
{
    /**
     * Display all saved addresses
     */
    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->get();
        
        return Inertia::render('Customer/Addresses/Index', [
            'addresses' => $addresses,
            'cities' => config('cavite.cities'),
            'barangays' => config('cavite.barangays'),
        ]);
    }
    
    /**
     * Store a new address
     */
    public function store(Request $request)
    {
        $cityKeys = array_keys(config('cavite.cities', []));
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:255',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'address_line' => 'required|string|max:500',
            'barangay' => 'required|string|max:100',
            'city' => ['required', 'string', Rule::in($cityKeys)],
            'province' => 'required|string|in:Cavite',
            'zip_code' => 'required|string|max:10',
            'is_default' => 'boolean',
            'latitude' => 'required|numeric|between:14.00,14.60',
            'longitude' => 'required|numeric|between:120.50,121.20',
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09, and contain numbers only.',
        ]);
        $validated['zip_code'] = data_get(config('cavite.cities'), $validated['city'] . '.zip', $validated['zip_code']);
        
        // If setting as default, unset other defaults
        if ($request->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }
        
        $address = auth()->user()->addresses()->create($validated);
        
        return back()->with('success', 'Address saved successfully!');
    }
    
    /**
     * Update an existing address
     */
    public function update(Request $request, CustomerAddress $address)
    {
        // Ensure user owns this address
        if ($address->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        $cityKeys = array_keys(config('cavite.cities', []));
        $validated = $request->validate([
            'label' => 'nullable|string|max:50',
            'recipient_name' => 'required|string|max:255',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'address_line' => 'required|string|max:500',
            'barangay' => 'required|string|max:100',
            'city' => ['required', 'string', Rule::in($cityKeys)],
            'province' => 'required|string|in:Cavite',
            'zip_code' => 'required|string|max:10',
            'is_default' => 'boolean',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09, and contain numbers only.',
        ]);
        $validated['zip_code'] = data_get(config('cavite.cities'), $validated['city'] . '.zip', $validated['zip_code']);
        
        // If setting as default, unset other defaults
        if ($request->is_default) {
            auth()->user()->addresses()->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }
        
        $address->update($validated);
        
        return back()->with('success', 'Address updated successfully!');
    }
    
    /**
     * Set an address as default
     */
    public function setDefault(CustomerAddress $address)
    {
        // Ensure user owns this address
        if ($address->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        // Unset all other defaults
        auth()->user()->addresses()->update(['is_default' => false]);
        
        // Set this as default
        $address->update(['is_default' => true]);
        
        return back()->with('success', 'Default address updated!');
    }
    
    /**
     * Delete an address
     */
    public function destroy(CustomerAddress $address)
    {
        // Ensure user owns this address
        if ($address->user_id !== auth()->id()) {
            return back()->with('error', 'Unauthorized');
        }
        
        $address->delete();
        
        return back()->with('success', 'Address deleted successfully!');
    }
}
