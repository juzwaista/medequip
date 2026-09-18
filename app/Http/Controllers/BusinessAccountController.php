<?php

namespace App\Http\Controllers;

use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Rules\SafeUpload;

class BusinessAccountController extends Controller
{
    /**
     * Show the B2B application form (for existing customers without a profile).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Already a verified distributor — they auto-qualify, no need to apply
        if ($user->role === 'distributor' && $user->distributor?->is_verified) {
            return redirect()->route('products.index')
                ->with('info', 'You already have B2B wholesale access as a verified distributor.');
        }

        // Already has a business profile — show status instead
        if ($user->businessProfile) {
            return redirect()->route('business-account.status');
        }

        return Inertia::render('BusinessAccount/Apply');
    }

    /**
     * Submit a B2B application.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Prevent duplicate applications
        if ($user->businessProfile) {
            return redirect()->route('business-account.status')
                ->with('info', 'You already have a business account application.');
        }

        $validated = $request->validate([
            'company_name'      => 'required|string|max:255',
            'business_type'     => 'required|string|max:255',
            'tin_number'        => 'nullable|string|max:50',
            'sec_dti_document'  => ['nullable', 'file', 'max:5120', new SafeUpload],
        ]);

        $docPath = null;
        if ($request->hasFile('sec_dti_document')) {
            $docPath = $request->file('sec_dti_document')->store('business-documents', 'public');
        }

        BusinessProfile::create([
            'user_id'               => $user->id,
            'company_name'          => $validated['company_name'],
            'business_type'         => $validated['business_type'],
            'tin_number'            => $validated['tin_number'] ?? null,
            'sec_dti_document_path' => $docPath,
            'status'                => 'pending',
        ]);

        return redirect()->route('business-account.status')
            ->with('success', 'Your B2B application has been submitted! We\'ll review it shortly.');
    }

    /**
     * Show the current application status.
     */
    public function status(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Verified distributor — no profile needed, show info
        if ($user->canAccessWholesale() && ! $user->businessProfile) {
            return Inertia::render('BusinessAccount/Status', [
                'autoQualified' => true,
            ]);
        }

        if (! $user->businessProfile) {
            return redirect()->route('business-account.apply');
        }

        return Inertia::render('BusinessAccount/Status', [
            'profile'       => $user->businessProfile,
            'autoQualified' => false,
        ]);
    }

    /**
     * Upload the missing SEC/DTI document for an existing business profile.
     */
    public function uploadDocument(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->businessProfile;

        if (!$profile) {
            return redirect()->route('business-account.apply');
        }

        $request->validate([
            'sec_dti_document' => ['required', 'file', 'max:5120', new SafeUpload],
        ]);

        if ($profile->sec_dti_document_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->sec_dti_document_path);
        }

        $docPath = $request->file('sec_dti_document')->store('business-documents', 'public');

        $profile->update([
            'sec_dti_document_path' => $docPath,
        ]);

        return redirect()->back()->with('success', 'Document uploaded successfully! Our team will review your application soon.');
    }
}
