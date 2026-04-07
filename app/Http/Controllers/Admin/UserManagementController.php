<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminInvitation;
use App\Notifications\AdminInvitation as AdminInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $search = $request->query('search', '');
        $shopStatus = $request->query('shop_status', 'all');

        $admins = User::whereIn('role', ['admin', 'super_admin'])
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->get();

        $distributorsQuery = \App\Models\Distributor::with('owner')
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('company_name', 'like', "%{$search}%")->orWhereHas('owner', fn ($q3) => $q3->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->when($shopStatus !== 'all', fn ($q) => $q->where('status', $shopStatus))
            ->orderByDesc('created_at');

        $distributors = $distributorsQuery->get()->map(fn ($d) => [
            'id' => $d->id,
            'company_name' => $d->company_name,
            'owner_name' => $d->owner?->name ?? '—',
            'owner_email' => $d->owner?->email ?? '—',
            'status' => $d->status,
            'contact_number' => $d->contact_number,
            'address' => $d->address,
            'created_at' => $d->created_at->toDateString(),
            'is_suspended' => $d->suspended_until?->isFuture() ?? false,
            'suspended_until' => $d->suspended_until?->format('M d, Y h:i A'),
            'dti_sec_path' => $d->dti_sec_path,
            'business_license_path' => $d->business_license_path,
            'bir_form_path' => $d->bir_form_path,
            'fda_license_path' => $d->fda_license_path,
            'prc_id_path' => $d->prc_id_path,
            'valid_id_path' => $d->valid_id_path,
            'authorization_letter_path' => $d->authorization_letter_path,
        ]);

        $platformUsers = User::whereIn('role', ['customer', 'courier'])
            ->when($search, fn ($q) => $q->where(fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'role', 'created_at', 'banned_at', 'ban_reason']);

        $shopCounts = [
            'all' => \App\Models\Distributor::count(),
            'pending' => \App\Models\Distributor::where('status', 'pending')->count(),
            'approved' => \App\Models\Distributor::where('status', 'approved')->count(),
            'rejected' => \App\Models\Distributor::where('status', 'rejected')->count(),
            'banned' => \App\Models\Distributor::where('status', 'banned')->count(),
        ];

        return Inertia::render('Admin/UserManagement/Index', [
            'admins' => $admins,
            'distributors' => $distributors,
            'platformUsers' => $platformUsers,
            'isSuperAdmin' => $user->role === 'super_admin',
            'filters' => ['search' => $search, 'shop_status' => $shopStatus],
            'shopCounts' => $shopCounts,
        ]);
    }

    /**
     * Store a newly created admin invitation.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if ($currentUser->role !== 'super_admin') {
            abort(403, 'Only Super Admins can issue new admin invitations.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
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
        Log::info('Admin Invitation Issued', [
            'invited_email' => $request->email,
            'issued_by' => $currentUser->id,
            'invitation_id' => $invitation->id,
        ]);

        return redirect()->back()->with('success', 'Admin invitation sent successfully to ' . $request->email);
    }

    /**
     * Update user role (super admin only).
     */
    public function updateRole(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        if (! $currentUser || $currentUser->role !== 'super_admin') {
            abort(403, 'Only Super Admins can update user roles.');
        }

        $validated = $request->validate([
            'role' => 'required|in:customer,courier,distributor,staff,admin',
        ]);

        // Do not allow role changes for super admins from this endpoint.
        if ($user->role === 'super_admin') {
            return redirect()->back()->with('error', 'Super Admin role cannot be changed.');
        }

        $user->forceFill(['role' => $validated['role']])->save();

        return redirect()->back()->with('success', 'User role updated successfully.');
    }

    /**
     * Ban a user with a reason.
     */
    public function ban(Request $request, User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        if (! $currentUser || ! in_array($currentUser->role, ['admin', 'super_admin'])) {
            abort(403, 'Not authorized.');
        }

        if ($user->role === 'super_admin') {
            return redirect()->back()->with('error', 'Super Admins cannot be banned.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->forceFill([
            'banned_at' => now(),
            'ban_reason' => $validated['reason'],
        ])->save();

        return redirect()->back()->with('success', "User \"{$user->name}\" has been banned.");
    }

    /**
     * Unban a user.
     */
    public function unban(User $user)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();
        if (! $currentUser || ! in_array($currentUser->role, ['admin', 'super_admin'])) {
            abort(403, 'Not authorized.');
        }

        $user->forceFill([
            'banned_at' => null,
            'ban_reason' => null,
        ])->save();

        return redirect()->back()->with('success', "User \"{$user->name}\" has been unbanned.");
    }
}
