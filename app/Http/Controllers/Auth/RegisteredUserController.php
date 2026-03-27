<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'cities' => config('cavite.cities'),
            'barangays' => config('cavite.barangays'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $cityKeys = array_keys(config('cavite.cities', []));

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:customer,distributor'],
            'contact_number' => ['required_if:role,customer', 'nullable', 'regex:/^09[0-9]{9}$/'],
            'address_line' => ['required_if:role,customer', 'nullable', 'string', 'max:500'],
            'city' => ['required_if:role,customer', 'nullable', 'string', Rule::in($cityKeys)],
            'barangay' => ['required_if:role,customer', 'nullable', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09 and contain only numbers.',
            'contact_number.required_if' => 'Contact number is required.',
            'address_line.required_if' => 'Complete Address is required.',
            'city.required_if' => 'City is required.',
            'barangay.required_if' => 'Barangay is required.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->input('role', 'customer'),
        ]);

        if ($user->role === 'customer') {
            $zipCode = data_get(config('cavite.cities'), $request->city . '.zip', '');
            
            $user->addresses()->create([
                'label' => $request->label ?: 'Home',
                'recipient_name' => $request->recipient_name ?: $user->name,
                'contact_number' => $request->contact_number,
                'address_line' => $request->address_line,
                'barangay' => $request->barangay,
                'city' => $request->city,
                'province' => 'Cavite',
                'zip_code' => $zipCode,
                'is_default' => true,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on role
        if ($user->role === 'distributor') {
            return redirect()->route('owner.distributors.create')
                ->with('info', 'Welcome! Please set up your business profile to get started.');
        }

        return redirect(route('dashboard', absolute: false));
    }
}
