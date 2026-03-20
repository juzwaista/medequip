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
        
        $admins = User::where('role', 'admin')
            ->orderBy('is_super_admin', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/UserManagement/Index', [
            'admins' => $admins,
            'isSuperAdmin' => $user->is_super_admin,
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
