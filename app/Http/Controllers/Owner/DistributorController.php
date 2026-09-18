<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Rules\SafeUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

        $props = [
            'ownerEmail' => Auth::user()->email,
            'ownerPhone' => Auth::user()->phone_number,
            'cities' => config('cavite.cities'),
            'barangays' => config('cavite.barangays'),
        ];

        $businessProfile = Auth::user()->businessProfile;

        if ($existing) {
            if ($existing->status === 'rejected') {
                // Return the view with the existing data so they can resubmit only what was wrong.
                return \Inertia\Inertia::render('Owner/Distributor/Create', array_merge($props, [
                    'existingDistributor' => [
                        'company_name'      => $existing->company_name,
                        'address_line'      => $existing->address,
                        'city'              => $existing->city,
                        'barangay'          => $existing->barangay,
                        'contact_number'    => $existing->contact_number,
                        'email'             => $existing->email,
                        'latitude'          => $existing->latitude,
                        'longitude'         => $existing->longitude,
                        'rejection_reason'  => $existing->rejection_reason,
                        // Document presence flags (not paths — paths stay secure on server)
                        'has_valid_id'             => !empty($existing->valid_id_path),
                        'has_business_license'     => !empty($existing->business_license_path),
                        'has_dti_sec'              => !empty($existing->dti_sec_path),
                        'has_bir_form'             => !empty($existing->bir_form_path),
                        'has_fda_license'          => !empty($existing->fda_license_path),
                        'has_prc_id'               => !empty($existing->prc_id_path),
                        'has_authorization_letter' => !empty($existing->authorization_letter_path),
                        // Expiry dates (pre-fill these so user doesn't re-enter them)
                        'valid_id_expires_at'              => $existing->valid_id_expires_at?->format('Y-m-d'),
                        'business_license_expires_at'      => $existing->business_license_expires_at?->format('Y-m-d'),
                        'dti_sec_expires_at'               => $existing->dti_sec_expires_at?->format('Y-m-d'),
                        'bir_form_expires_at'              => $existing->bir_form_expires_at?->format('Y-m-d'),
                        'fda_license_expires_at'           => $existing->fda_license_expires_at?->format('Y-m-d'),
                        'prc_id_expires_at'                => $existing->prc_id_expires_at?->format('Y-m-d'),
                    ],
                ]));
            }
            if ($existing->status === 'pending') {
                return redirect()->route('owner.distributors.pending')
                    ->with('info', 'Your application is already under review.');
            }

            return redirect()->route('owner.dashboard');
        }

        if ($businessProfile && $businessProfile->status === 'approved') {
            $props['existingDistributor'] = [
                'company_name' => $businessProfile->company_name,
            ];
        }

        return \Inertia\Inertia::render('Owner/Distributor/Create', $props);
    }

    public function store(Request $request)
    {
        $cityKeys = array_keys(config('cavite.cities', []));

        $existing = Distributor::where('user_id', auth()->id())->first();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address_line' => 'required|string|max:255',
            'city' => ['required', 'string', Rule::in($cityKeys)],
            'barangay' => 'required|string|max:100',
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'email' => 'required|email|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'valid_id' => [($existing && $existing->valid_id_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'business_license' => [($existing && $existing->business_license_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'dti_sec' => [($existing && $existing->dti_sec_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'bir_form' => [($existing && $existing->bir_form_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'fda_license' => [($existing && $existing->fda_license_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'prc_id' => [($existing && $existing->prc_id_path) ? 'nullable' : 'required', 'file', 'max:10240', SafeUpload::document()],
            'authorization_letter' => ['nullable', 'file', 'max:10240', SafeUpload::document()],
            // Expiration dates
            'valid_id_expires_at' => 'required|date',
            'business_license_expires_at' => 'required|date',
            'dti_sec_expires_at' => 'required|date',
            'bir_form_expires_at' => 'required|date',
            'fda_license_expires_at' => 'required|date',
            'prc_id_expires_at' => 'required|date',
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09, and contain numbers only.',
            'latitude.required' => 'Please pin your business location on the map.',
            'longitude.required' => 'Please pin your business location on the map.',
            'address_line.required' => 'Street / Building / Unit is required.',
        ]);

        // Store the uploaded files securely on the local disk
        $validIdPath = $request->hasFile('valid_id') ? $request->file('valid_id')->store('distributor_documents/valid_ids', 'local') : $existing->valid_id_path;
        $licensePath = $request->hasFile('business_license') ? $request->file('business_license')->store('distributor_documents/licenses', 'local') : $existing->business_license_path;
        $dtiSecPath = $request->hasFile('dti_sec') ? $request->file('dti_sec')->store('distributor_documents/dti_sec', 'local') : $existing->dti_sec_path;
        $birFormPath = $request->hasFile('bir_form') ? $request->file('bir_form')->store('distributor_documents/bir_forms', 'local') : $existing->bir_form_path;
        $fdaLicensePath = $request->hasFile('fda_license') ? $request->file('fda_license')->store('distributor_documents/fda_licenses', 'local') : $existing->fda_license_path;
        $prcIdPath = $request->hasFile('prc_id') ? $request->file('prc_id')->store('distributor_documents/prc_ids', 'local') : $existing->prc_id_path;

        $authLetterPath = $existing ? $existing->authorization_letter_path : null;
        if ($request->hasFile('authorization_letter')) {
            $authLetterPath = $request->file('authorization_letter')->store('distributor_documents/auth_letters', 'local');
        }

        $zipCode = data_get(config('cavite.cities'), $validated['city'].'.zip', '');
        
        // Full address string for backward compatibility
        $fullAddress = "{$validated['address_line']}, Brgy. {$validated['barangay']}, {$validated['city']}, Cavite";

        Distributor::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'company_name' => $validated['company_name'],
                'address' => $fullAddress,
                'city' => $validated['city'],
                'barangay' => $validated['barangay'],
                'province' => 'Cavite',
                'zip_code' => $zipCode,
                'contact_number' => $validated['contact_number'],
                'email' => $validated['email'],
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'valid_id_path' => $validIdPath,
                'business_license_path' => $licensePath,
                'dti_sec_path' => $dtiSecPath,
                'bir_form_path' => $birFormPath,
                'fda_license_path' => $fdaLicensePath,
                'prc_id_path' => $prcIdPath,
                'authorization_letter_path' => $authLetterPath,
                'valid_id_expires_at' => $validated['valid_id_expires_at'],
                'business_license_expires_at' => $validated['business_license_expires_at'],
                'dti_sec_expires_at' => $validated['dti_sec_expires_at'],
                'bir_form_expires_at' => $validated['bir_form_expires_at'],
                'fda_license_expires_at' => $validated['fda_license_expires_at'],
                'prc_id_expires_at' => $validated['prc_id_expires_at'],
                'status' => 'pending',
                'is_verified' => 0,
                'rejection_reason' => null,
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
        $user = Auth::user();
        $distributor = Distributor::where('user_id', $user->id)->first();

        if ($distributor && $distributor->status === 'approved') {
            return redirect()->route('owner.shop.setup');
        }

        return Inertia::render('Owner/Distributor/Pending', [
            'distributor' => $distributor,
            'status' => $distributor?->status ?? 'pending',
        ]);
    }
}
