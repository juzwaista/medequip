<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Auth\Events\Registered;

class CorporateController extends Controller
{
    /**
     * Display the B2B corporate landing page.
     */
    public function index()
    {
        return Inertia::render('Corporate/Index');
    }

    /**
     * Handle an incoming B2B registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        BusinessProfile::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'business_type' => $request->business_type,
            'status' => 'pending', // Requires TIN later to transact
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welcome to MedEquip B2B! You can now browse wholesale prices.');
    }
}
