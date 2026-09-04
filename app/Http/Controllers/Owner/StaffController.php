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
use App\Mail\StaffInviteEmail;

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
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'invite_method' => 'required|in:email,password',
            'password' => ['required_if:invite_method,password', 'nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $password = $request->invite_method === 'email' ? Str::password(12) : $request->password;

        $staffUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $password,
            'distributor_id' => $distributor->id,
            'email_verified_at' => $request->invite_method === 'password' ? now() : null,
        ]);

        $staffUser->forceFill(['role' => 'staff'])->save();

        if ($request->invite_method === 'email') {
            Mail::to($staffUser->email)->send(new StaffInviteEmail($staffUser, $password));
        }

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            // Scope to the distributor's team before assigning permissions
            setPermissionsTeamId($distributor->id);
            if ($request->has('permissions')) {
                $staffUser->syncPermissions($request->permissions);
            }
        }

        return redirect()->back()->with('success', 'Staff account created successfully.');
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
