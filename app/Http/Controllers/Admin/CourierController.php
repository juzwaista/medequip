<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\User;
use App\Notifications\CourierAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'couriers' => $couriers,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username', ''))),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => ['nullable', 'regex:/^09[0-9]{9}$/'],
            'vehicle_type' => 'nullable|string|max:255',
            'plate_number' => 'nullable|string|max:255',
        ], [
            'phone_number.regex' => 'Phone number must be 11 digits, start with 09, and contain numbers only.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make(Str::password(32)),
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
            'role' => 'courier',
        ])->save();

        Courier::create([
            'user_id' => $user->id,
            'vehicle_type' => $request->vehicle_type,
            'plate_number' => $request->plate_number,
            'status' => 'active',
        ]);

        $token = Password::getRepository()->create($user);
        $user->notify(new CourierAccountCreated($token));

        return back()->with(
            'success',
            'Courier account created. They have been emailed a link to set their password and activate their account.'
        );
    }
}
