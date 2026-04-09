<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Rules\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ShopProfileOnboardingController extends Controller
{
    public function edit(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'distributor') {
            return redirect()->route('owner.dashboard');
        }

        $distributor = $user->distributor;
        if (! $distributor || $distributor->status !== 'approved') {
            return redirect()->route('owner.dashboard');
        }

        if ($distributor->shop_profile_onboarding_completed_at !== null) {
            return redirect()->route('owner.dashboard');
        }

        $suggested = $distributor->slug ?: Str::slug($distributor->company_name);
        if ($suggested === '') {
            $suggested = 'shop-'.$distributor->id;
        }

        return Inertia::render('Owner/ShopProfile/Onboarding', [
            'distributor' => [
                'id' => $distributor->id,
                'company_name' => $distributor->company_name,
                'slug' => $distributor->slug ?? $suggested,
                'suggested_slug' => $suggested,
                'description' => $distributor->description ?? '',
                'phone' => $distributor->phone ?? $distributor->contact_number,
            ],
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->role !== 'distributor') {
            abort(403);
        }

        $distributor = $user->distributor;
        if (! $distributor || $distributor->status !== 'approved') {
            abort(403);
        }

        if ($distributor->shop_profile_onboarding_completed_at !== null) {
            return redirect()->route('owner.dashboard');
        }

        $validated = $request->validate([
            'slug' => 'required|alpha_dash|unique:distributors,slug,'.$distributor->id,
            'description' => 'required|string|min:30|max:2000',
            'phone' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'pickup_instructions' => 'nullable|string|max:5000',
            'logo' => ['nullable', 'image', 'max:2048', SafeUpload::image()],
            'cover_photo' => ['nullable', 'image', 'max:5120', SafeUpload::image()],
        ], [
            'phone.regex' => 'Phone number must be 11 digits, start with 09, and contain numbers only.',
            'description.min' => 'Please write at least a short paragraph (30+ characters) so customers know your shop.',
        ]);

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

        unset($validated['logo'], $validated['cover_photo']);
        $validated['phone'] = ($validated['phone'] ?? '') !== '' ? $validated['phone'] : null;

        $distributor->update(array_merge($validated, [
            'shop_profile_onboarding_completed_at' => now(),
        ]));

        return redirect()
            ->route('owner.inventory.create')
            ->with('success', 'Your public shop is live. Add your first product to start selling.');
    }
}
