<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DistributorController extends Controller
{
    public function index()
    {
        // One distributor per user
        $distributor = Distributor::where('user_id', Auth::id())->first();

        if ($distributor) {
            return redirect()->route('owner.profile.edit');
        }

        return redirect()->route('owner.distributor.create');
    }

    public function create()
    {
        $existing = Distributor::where('user_id', Auth::id())->first();

        if ($existing) {
            if ($existing->status === 'rejected') {
                // Don't delete — reset in place to avoid FK constraint failures (orders etc.)
                // Clear the old document paths and reset status so the form can re-submit
                $existing->update([
                    'status'           => null,   // temporarily nulled; store() sets to pending
                    'rejection_reason' => null,
                    'valid_id_path'    => null,
                    'business_license_path' => null,
                    'is_verified'      => 0,
                ]);
                return \Inertia\Inertia::render('Owner/Distributor/Create');
            }
            if ($existing->status === 'pending') {
                return redirect()->route('owner.distributors.pending')
                    ->with('info', 'Your application is already under review.');
            }
            return redirect()->route('owner.dashboard');
        }

        return \Inertia\Inertia::render('Owner/Distributor/Create');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'     => 'required|string|max:255',
            'address'          => 'required|string|max:255',
            'contact_number'   => 'required|string|max:50',
            'email'            => 'required|email|max:255',
            'valid_id'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'business_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Store the uploaded files
        $validIdPath = $request->file('valid_id')->store('distributor_documents/valid_ids', 'public');
        $licensePath = $request->file('business_license')->store('distributor_documents/licenses', 'public');

        Distributor::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'company_name'          => $validated['company_name'],
                'address'               => $validated['address'],
                'contact_number'        => $validated['contact_number'],
                'email'                 => $validated['email'],
                'valid_id_path'         => $validIdPath,
                'business_license_path' => $licensePath,
                'status'                => 'pending',
                'is_verified'           => 0,
                'rejection_reason'      => null,
            ]
        );

        return redirect()
            ->route('owner.distributors.pending')
            ->with('success', 'Application submitted! We\'ll review your documents and get back to you soon.');
    }

    /**
     * Show the pending/rejected application status page.
     */
    public function pending()
    {
        $user        = Auth::user();
        $distributor = Distributor::where('user_id', $user->id)->first();

        return Inertia::render('Owner/Distributor/Pending', [
            'distributor' => $distributor,
            'status'      => $distributor?->status ?? 'pending',
        ]);
    }
}
