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
            'username' => 'required|string|max:255|unique:users,username',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $tokenHash = hash('sha256', $request->token);
        $invitation = AdminInvitation::where('email', $request->email)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            abort(403, 'Invalid invitation.');
        }

        $displayName = \Illuminate\Support\Str::title(str_replace('_', ' ', $request->username));

        // Create the Admin User
        $user = User::create([
            'name' => $displayName,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password, // Password is hashed automatically in User model or here
        ]);

        $user->forceFill([
            'role' => 'admin',
            'email_verified_at' => now(), // They verified by clicking the link
        ])->save();

        if ($invitation->role_id) {
            $role = \Spatie\Permission\Models\Role::find($invitation->role_id);
            if ($role) {
                // Ensure team scoping is disabled for platform roles
                setPermissionsTeamId(null);
                $user->assignRole($role);
            }
        }

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
