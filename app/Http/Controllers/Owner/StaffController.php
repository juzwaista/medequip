<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Mail\StaffInviteEmail;
use App\Models\StaffInvitation;

class StaffController extends Controller
{
    /**
     * Display a listing of the shop's staff members.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Only the actual distributor owner can manage staff
        if ($user->role !== 'distributor') {
            abort(403, 'Only the shop owner can manage staff.');
        }

        $distributor = $user->distributor;

        if (! $distributor) {
            abort(404, 'Distributor profile not found.');
        }

        $staff = User::where('distributor_id', $distributor->id)
            ->where('role', 'staff')
            ->with(['roles', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->get();

        $shopRoles = class_exists(\Spatie\Permission\Models\Role::class) 
            ? \Spatie\Permission\Models\Role::with('permissions')->where('distributor_id', $distributor->id)->get(['id', 'name', 'description']) 
            : [];

        $permissions = class_exists(\Spatie\Permission\Models\Permission::class)
            ? \Spatie\Permission\Models\Permission::where('guard_name', 'web')
                ->where('name', 'like', 'shop.%')
                ->get()
                ->groupBy(function ($perm) {
                    $parts = explode('.', $perm->name);
                    return ucfirst($parts[1] ?? 'General');
                })
            : [];

        return Inertia::render('Owner/Staff/Index', [
            'staffMembers' => $staff,
            'shopRoles' => $shopRoles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created staff account.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role !== 'distributor') {
            abort(403, 'Only the shop owner can create staff accounts.');
        }

        $distributor = $user->distributor;

        $request->validate([
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $token = Str::random(32);

        // Remove any existing invitation for this email
        StaffInvitation::where('email', $request->email)->delete();

        $invitation = StaffInvitation::create([
            'email' => $request->email,
            'token_hash' => Hash::make($token),
            'distributor_id' => $distributor->id,
            'invited_by_id' => $user->id,
            'permissions' => $request->permissions,
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($request->email)->send(new StaffInviteEmail($request->email, $token));

        return redirect()->back()->with('success', 'Staff invitation sent successfully.');
    }

    /**
     * Remove the specified staff account.
     */
    public function destroy($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role !== 'distributor') {
            abort(403, 'Only the shop owner can remove staff.');
        }

        $staff = User::where('distributor_id', $user->distributor->id)
            ->where('role', 'staff')
            ->findOrFail($id);

        $staff->delete();

        return redirect()->back()->with('success', 'Staff account archived successfully.');
    }
}
