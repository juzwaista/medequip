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
                    'rejection_reason' => null,
                    'valid_id_path'    => null,
                    'business_license_path' => null,
                    'dti_sec_path'          => null,
                    'bir_form_path'         => null,
                    'fda_license_path'      => null,
                    'prc_id_path'           => null,
                    'authorization_letter_path' => null,
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
            'contact_number'   => ['required', 'regex:/^09[0-9]{9}$/'],
            'email'            => 'required|email|max:255',
            'valid_id'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'business_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Business Permit
            'dti_sec'          => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bir_form'         => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'fda_license'      => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'prc_id'           => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'authorization_letter' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09, and contain numbers only.',
        ]);

        // Store the uploaded files securely on the local disk
        $validIdPath = $request->file('valid_id')->store('distributor_documents/valid_ids', 'local');
        $licensePath = $request->file('business_license')->store('distributor_documents/licenses', 'local');
        $dtiSecPath = $request->file('dti_sec')->store('distributor_documents/dti_sec', 'local');
        $birFormPath = $request->file('bir_form')->store('distributor_documents/bir_forms', 'local');
        $fdaLicensePath = $request->file('fda_license')->store('distributor_documents/fda_licenses', 'local');
        $prcIdPath = $request->file('prc_id')->store('distributor_documents/prc_ids', 'local');
        
        $authLetterPath = null;
        if ($request->hasFile('authorization_letter')) {
            $authLetterPath = $request->file('authorization_letter')->store('distributor_documents/auth_letters', 'local');
        }

        Distributor::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'company_name'          => $validated['company_name'],
                'address'               => $validated['address'],
                'contact_number'        => $validated['contact_number'],
                'email'                 => $validated['email'],
                'valid_id_path'         => $validIdPath,
                'business_license_path' => $licensePath,
                'dti_sec_path'          => $dtiSecPath,
                'bir_form_path'         => $birFormPath,
                'fda_license_path'      => $fdaLicensePath,
                'prc_id_path'           => $prcIdPath,
                'authorization_letter_path' => $authLetterPath,
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
