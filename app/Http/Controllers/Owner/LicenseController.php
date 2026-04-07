<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\License;
use App\Rules\SafeUpload;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicenseController extends Controller
{
    public function create(Distributor $distributor)
    {
        // Ensure the authenticated user owns this distributor
        if ($distributor->user_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Owner/License/Create', [
            'distributor' => $distributor,
        ]);
    }

    public function store(Request $request, Distributor $distributor)
    {
        if ($distributor->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'type' => 'required|in:fda,business_permit,phic,doh,other',
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120', SafeUpload::document()],
        ]);

        $path = $request->file('file')->store('licenses', 'public');

        License::create([
            'distributor_id' => $distributor->id,
            'type' => $request->type,
            'file_path' => $path,
        ]);

        return redirect()
            ->route('owner.distributors.index')
            ->with('success', 'License submitted for verification.');
    }
}
