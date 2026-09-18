<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends Controller
{
    /**
     * List all B2B applications with optional status filter.
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status', 'pending');

        $profiles = BusinessProfile::with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/BusinessProfiles/Index', [
            'profiles' => $profiles,
            'filters'  => ['status' => $status],
        ]);
    }

    /**
     * Show a single B2B application for review.
     */
    public function show(BusinessProfile $profile): Response
    {
        $profile->load('user');

        return Inertia::render('Admin/BusinessProfiles/Show', [
            'profile' => $profile,
        ]);
    }

    /**
     * Approve a B2B application.
     */
    public function approve(BusinessProfile $profile): RedirectResponse
    {
        $profile->update([
            'status'           => 'approved',
            'rejection_reason' => null,
        ]);

        return back()->with('success', "{$profile->company_name} has been approved for B2B access.");
    }

    /**
     * Reject a B2B application.
     */
    public function reject(Request $request, BusinessProfile $profile): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $profile->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', "{$profile->company_name} has been rejected.");
    }
}
