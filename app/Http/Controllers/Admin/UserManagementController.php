<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    /**
     * Display a listing of admin users.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $admins = User::whereIn('role', ['admin', 'super_admin'])
            ->orderBy('created_at', 'desc')
            ->get();

        $distributors = \App\Models\Distributor::with('owner')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($d) => [
                'id'           => $d->id,
                'company_name' => $d->company_name,
                'owner_name'   => $d->owner?->name ?? '—',
                'owner_email'  => $d->owner?->email ?? '—',
                'status'       => $d->status,
                'created_at'   => $d->created_at->toDateString(),
            ]);

        $platformUsers = User::whereIn('role', ['customer', 'courier'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'role', 'created_at']);

        return Inertia::render('Admin/UserManagement/Index', [
            'admins'        => $admins,
            'distributors'  => $distributors,
            'platformUsers' => $platformUsers,
            'isSuperAdmin'  => $user->role === 'super_admin',
        ]);
    }

    /**
     * Store a newly created admin in storage.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = auth()->user();

        if (!$currentUser->is_super_admin) {
            abort(403, 'Only Super Admins can create new admin accounts.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        return redirect()->back()->with('success', 'Admin account created successfully.');
    }
}
