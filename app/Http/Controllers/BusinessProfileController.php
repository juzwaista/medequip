<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class BusinessProfileController extends Controller
{
    /**
     * Show the application form for a B2B account.
     */
    public function create(Request $request)
    {
        $user = $request->user();
        
        // If they already have a business profile, redirect based on status
        if ($user->business_profile) {
            return redirect()->route('products.index')
                ->with('info', 'You have already applied for a Business Account. Current status: ' . ucfirst($user->business_profile->status));
        }

        return Inertia::render('Business/Apply');
    }

    /**
     * Store the B2B account application.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->business_profile) {
            return redirect()->route('products.index')->with('error', 'You already have a business profile.');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string|in:hospital,clinic,pharmacy,distributor,other',
            'tin_number' => 'required|string|max:50',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ]);

        $path = $request->file('document')->store('business_documents', 'public');

        BusinessProfile::create([
            'user_id' => $user->id,
            'company_name' => $validated['company_name'],
            'business_type' => $validated['business_type'],
            'tin_number' => $validated['tin_number'],
            'sec_dti_document_path' => $path,
            'status' => 'pending',
        ]);

        return redirect()->route('products.index')->with('success', 'Your Business Account application has been submitted and is under review.');
    }
}
