<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Rules\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $distributor = $this->getDistributor();

        if (! $distributor) {
            return redirect()->route('dashboard')
                ->with('error', 'Distributor profile not found');
        }

        // Match inventory: list all catalog rows (not only active) so owners can set featured flags;
        // the public shop still only surfaces active products in DistributorProfileController.
        $products = Product::where('distributor_id', $distributor->id)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_featured', 'is_active']);

        $defaultTemplates = config('order_chat.defaults', []);

        return Inertia::render('Owner/Profile/Edit', [
            'distributor' => $distributor,
            'products' => $products,
            'defaultTemplates' => $defaultTemplates,
            'cities' => config('cavite.cities', []),
            'barangays' => config('cavite.barangays', []),
        ]);
    }

    public function update(Request $request)
    {
        $distributor = $this->getDistributor();

        if (! $distributor) {
            return back()->with('error', 'Distributor profile not found');
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'slug' => 'required|alpha_dash|unique:distributors,slug,'.$distributor->id,
            'description' => 'nullable|string|max:2000',
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'website' => 'nullable|url',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'barangay' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_hours' => 'nullable|array',
            'business_hours.*.day' => 'required_with:business_hours|string',
            'business_hours.*.open' => 'nullable|string',
            'business_hours.*.close' => 'nullable|string',
            'business_hours.*.closed' => 'nullable|boolean',
            'social_links' => 'nullable|array',
            'social_links.facebook' => 'nullable|url|max:500',
            'social_links.instagram' => 'nullable|url|max:500',
            'social_links.tiktok' => 'nullable|url|max:500',
            'logo' => ['nullable', 'image', 'max:2048', SafeUpload::image()],
            'cover_photo' => ['nullable', 'image', 'max:5120', SafeUpload::image()],
            'chat_template_order_accepted' => 'nullable|string|max:5000',
            'chat_template_order_shipped' => 'nullable|string|max:5000',
            'chat_auto_reply' => 'nullable|string|max:5000',
            'featured_product_ids' => 'nullable|array|max:8',
            'featured_product_ids.*' => 'integer|exists:products,id',
            'max_cod_amount' => 'nullable|numeric|min:0',
            'pickup_instructions' => 'nullable|string|max:5000',
            // Expiration dates
            'valid_id_expires_at' => 'nullable|date',
            'business_license_expires_at' => 'nullable|date',
            'dti_sec_expires_at' => 'nullable|date',
            'bir_form_expires_at' => 'nullable|date',
            'fda_license_expires_at' => 'nullable|date',
            'prc_id_expires_at' => 'nullable|date',
            // New documents
            'valid_id_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            'business_license_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            'dti_sec_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            'bir_form_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            'fda_license_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            'prc_id_new' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
        ], [
            'phone.regex' => 'Phone number must be 11 digits, start with 09, and contain numbers only.',
        ]);

        // Handle specific document updates
        if ($request->hasFile('valid_id_new')) {
            $validated['valid_id_path'] = $request->file('valid_id_new')->store('distributor_documents/valid_ids', 'local');
        }
        if ($request->hasFile('business_license_new')) {
            $validated['business_license_path'] = $request->file('business_license_new')->store('distributor_documents/licenses', 'local');
        }
        if ($request->hasFile('dti_sec_new')) {
            $validated['dti_sec_path'] = $request->file('dti_sec_new')->store('distributor_documents/dti_sec', 'local');
        }
        if ($request->hasFile('bir_form_new')) {
            $validated['bir_form_path'] = $request->file('bir_form_new')->store('distributor_documents/bir_forms', 'local');
        }
        if ($request->hasFile('fda_license_new')) {
            $validated['fda_license_path'] = $request->file('fda_license_new')->store('distributor_documents/fda_licenses', 'local');
        }
        if ($request->hasFile('prc_id_new')) {
            $validated['prc_id_path'] = $request->file('prc_id_new')->store('distributor_documents/prc_ids', 'local');
        }

        if ($request->hasFile('logo')) {
            if ($distributor->logo_path) {
                Storage::disk('public')->delete($distributor->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('distributor-logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($distributor->cover_photo_path) {
                Storage::disk('public')->delete($distributor->cover_photo_path);
            }
            $validated['cover_photo_path'] = $request->file('cover_photo')->store('distributor-covers', 'public');
        }

        $featuredIds = $validated['featured_product_ids'] ?? [];
        unset($validated['featured_product_ids']);

        $distributor->update($validated);

        Product::where('distributor_id', $distributor->id)
            ->where('is_featured', true)
            ->whereNotIn('id', $featuredIds)
            ->update(['is_featured' => false]);

        if (! empty($featuredIds)) {
            Product::where('distributor_id', $distributor->id)
                ->whereIn('id', $featuredIds)
                ->update(['is_featured' => true]);
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    public function checkSlug(Request $request)
    {
        $slug = Str::slug($request->slug);
        $distributor = $this->getDistributor();
        if (! $distributor) {
            return response()->json(['available' => false, 'slug' => $slug], 403);
        }
        $distributorId = $distributor->id;

        $exists = \App\Models\Distributor::where('slug', $slug)
            ->where('id', '!=', $distributorId)
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'slug' => $slug,
        ]);
    }
}
