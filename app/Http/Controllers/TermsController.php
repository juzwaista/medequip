<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TermsController extends Controller
{
    /**
     * Accept the current platform terms and conditions.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'terms_accepted' => 'required|accepted',
            'redirect_to' => 'nullable|string',
        ], [
            'terms_accepted.accepted' => 'You must accept the Terms and Conditions to continue.',
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $user->update([
            'terms_accepted_at' => now(),
            'terms_version' => \App\Models\User::CURRENT_TERMS_VERSION,
        ]);

        $redirectTo = $request->input('redirect_to');

        // Allow only internal absolute paths; reject full URLs.
        if (! is_string($redirectTo) || ! str_starts_with($redirectTo, '/')) {
            $redirectTo = null;
        }

        // Safety: never redirect back to the POST endpoint.
        if ($redirectTo === '/terms/accept') {
            $redirectTo = null;
        }

        if (! $redirectTo) {
            $redirectTo = match ($user->role) {
                'admin', 'super_admin' => '/admin/dashboard',
                'courier' => '/courier/dashboard',
                'distributor', 'staff' => '/owner/dashboard',
                default => '/products',
            };
        }

        return redirect($redirectTo)->with('success', 'Terms accepted. Welcome to MedEquip!');
    }
}
