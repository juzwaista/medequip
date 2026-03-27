<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form
     */
    public function edit()
    {
        $distributor = auth()->user()->distributor;
        
        if (!$distributor) {
            return redirect()->route('dashboard')
                ->with('error', 'Distributor profile not found');
        }
        
        return Inertia::render('Owner/Profile/Edit', [
            'distributor' => $distributor
        ]);
    }
    
    /**
     * Update the distributor profile
     */
    public function update(Request $request)
    {
        $distributor = auth()->user()->distributor;
        
        if (!$distributor) {
            return back()->with('error', 'Distributor profile not found');
        }
        
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'slug' => 'required|alpha_dash|unique:distributors,slug,' . $distributor->id,
            'description' => 'nullable|string|max:2000',
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'website' => 'nullable|url',
            'logo' => 'nullable|image|max:2048', // 2MB
            'cover_photo' => 'nullable|image|max:5120', // 5MB
        ], [
            'phone.regex' => 'Phone number must be 11 digits, start with 09, and contain numbers only.',
        ]);
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($distributor->logo_path) {
                \Storage::disk('public')->delete($distributor->logo_path);
            }
            
            $logoPath = $request->file('logo')->store('distributor-logos', 'public');
            $validated['logo_path'] = $logoPath;
        }
        
        // Handle cover photo upload
        if ($request->hasFile('cover_photo')) {
            // Delete old cover
            if ($distributor->cover_photo_path) {
                \Storage::disk('public')->delete($distributor->cover_photo_path);
            }
            
            $coverPath = $request->file('cover_photo')->store('distributor-covers', 'public');
            $validated['cover_photo_path'] = $coverPath;
        }
        
        $distributor->update($validated);
        
        return back()->with('success', 'Profile updated successfully!');
    }
    
    /**
     * Check if slug is available
     */
    public function checkSlug(Request $request)
    {
        $slug = Str::slug($request->slug);
        $distributorId = auth()->user()->distributor->id;
        
        $exists = \App\Models\Distributor::where('slug', $slug)
            ->where('id', '!=', $distributorId)
            ->exists();
        
        return response()->json([
            'available' => !$exists,
            'slug' => $slug
        ]);
    }
}
