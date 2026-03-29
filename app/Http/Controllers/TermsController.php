<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class TermsController extends Controller
{
    /**
     * Accept the current platform terms and conditions.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'terms_accepted' => 'required|accepted',
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms and Conditions to continue.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->update([
            'terms_accepted_at' => now(),
            'terms_version' => \App\Models\User::CURRENT_TERMS_VERSION,
        ]);

        return back()->with('success', 'Terms accepted. Welcome to MedEquip!');
    }
}
