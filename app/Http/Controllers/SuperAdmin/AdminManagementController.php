<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminInvitation;
use App\Notifications\AdminInvitation as AdminInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AdminManagementController extends Controller
{
    public function index()
    {
        $admins = User::where('role', 'admin')->latest()->get();

        return Inertia::render('Admin/PlatformStaff/Index', [
            'admins' => $admins,
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        // Only super_admin can do this (protected by middleware already, but good for clarity)
        if ($currentUser->role !== 'super_admin') {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        // Generate high-entropy token
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);

        // Store invitation (Hashed)
        $invitation = AdminInvitation::create([
            'email' => $request->email,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(24),
            'invited_by_id' => $currentUser->id,
        ]);

        // Dispatch Notification (with raw token)
        Notification::route('mail', $request->email)
            ->notify(new AdminInvitationNotification($rawToken, $request->email));

        // Audit Log
        Log::info('Staff Admin Invitation Issued', [
            'invited_email' => $request->email,
            'issued_by' => $currentUser->id,
            'invitation_id' => $invitation->id,
        ]);

        return back()->with('success', 'Platform staff invitation sent successfully to ' . $request->email);
    }
}
