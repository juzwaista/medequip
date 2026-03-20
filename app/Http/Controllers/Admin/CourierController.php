<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class CourierController extends Controller
{
    public function index()
    {
        $couriers = User::where('role', 'courier')
            ->with('courier')
            ->latest()
            ->get();
            
        return Inertia::render('Admin/Couriers/Index', [
            'couriers' => $couriers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'password' => ['required', Password::defaults()],
            'vehicle_type' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'role' => 'courier',
        ]);

        Courier::create([
            'user_id' => $user->id,
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => $request->plate_number,
            'status' => 'active',
        ]);

        return back()->with('success', 'Courier account provisioned successfully.');
    }
}
