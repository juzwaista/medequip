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
        // One Distributor per User Policy
        if (Distributor::where('user_id', Auth::id())->exists()) {
            return redirect()->route('dashboard')
                ->with('info', 'You already have a registered distributor profile.');
        }

        return \Inertia\Inertia::render('Owner/Distributor/Create');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact_number' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'valid_id' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'business_license' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Store the uploaded files
        $validIdPath = $request->file('valid_id')->store('distributor_documents/valid_ids', 'public');
        $licensePath = $request->file('business_license')->store('distributor_documents/licenses', 'public');

        Distributor::create([
            'user_id' => auth()->id(),
            'company_name' => $validated['company_name'],
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'],
            'email' => $validated['email'],
            'valid_id_path' => $validIdPath,
            'business_license_path' => $licensePath,
            'is_verified' => 0,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Distributor added successfully. Your documents are pending verification.');
    }

}
