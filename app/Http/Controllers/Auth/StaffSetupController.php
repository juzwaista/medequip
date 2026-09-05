<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Support\Str;

class StaffSetupController extends Controller
{
    /**
     * Show the staff setup form.
     */
    public function show(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $tokenHash = hash('sha256', $request->token);
        $invitation = StaffInvitation::where('email', $request->email)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            return Inertia::render('Auth/InvitationInvalid', [
                'message' => 'This invitation link is invalid, expired, or has already been used.',
            ]);
        }

        return Inertia::render('Auth/StaffSetup', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    /**
     * Complete the staff setup and create the user.
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
        $invitation = StaffInvitation::where('email', $request->email)
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$invitation || $invitation->isExpired() || $invitation->isAccepted()) {
            abort(403, 'Invalid invitation.');
        }

        $displayName = Str::title(str_replace('_', ' ', $request->username));

        // Create the Staff User
        $user = User::create([
            'name' => $displayName,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'distributor_id' => $invitation->distributor_id,
        ]);

        $user->forceFill([
            'role' => 'staff',
            'email_verified_at' => now(), // They verified by clicking the link
        ])->save();

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            // Scope to the distributor's team before assigning permissions
            setPermissionsTeamId($invitation->distributor_id);
            if (!empty($invitation->permissions)) {
                $user->syncPermissions($invitation->permissions);
            }
        }

        // Invalidate the invitation
        $invitation->update([
            'accepted_at' => now(),
        ]);

        // Audit Log
        Log::info('Staff Invitation Accepted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'invitation_id' => $invitation->id,
        ]);

        return redirect()->route('login')->with('success', 'Account setup complete! You can now log in.');
    }
}
