<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class AdminSetupController extends Controller
{
    /**
     * Show the admin setup form.
     */
    public function show(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $tokenHash = hash('sha256', $request->token);
        $invitation = AdminInvitation::where('email', $request->email)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            return Inertia::render('Auth/InvitationInvalid', [
                'message' => 'This invitation link is invalid, expired, or has already been used.',
            ]);
        }

        return Inertia::render('Auth/AdminSetup', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Complete the admin setup and create the user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $tokenHash = hash('sha256', $request->token);
        $invitation = AdminInvitation::where('email', $request->email)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            abort(403, 'Invalid invitation.');
        }

        // Create the Admin User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Password is hashed automatically in User model or here
            'email_verified_at' => now(), // They verified by clicking the link
        ]);

        $user->forceFill(['role' => 'admin'])->save();

        // Invalidate the invitation
        $invitation->update([
            'accepted_at' => now(),
        ]);

        // Audit Log
        Log::info('Admin Invitation Accepted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'invitation_id' => $invitation->id,
        ]);

        return redirect()->route('login')->with('success', 'Account setup complete! You can now log in.');
    }
}
