<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;

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

        if (!$distributor) {
            abort(404, 'Distributor profile not found.');
        }

        $staff = User::where('distributor_id', $distributor->id)
            ->where('role', 'staff')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Owner/Staff/Index', [
            'staffMembers' => $staff
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
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'staff',
            'distributor_id' => $distributor->id,
        ]);

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

        return redirect()->back()->with('success', 'Staff account removed successfully.');
    }
}
