<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
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
     * Live username availability check for the registration form (JSON).
     */
    public function usernameAvailable(Request $request): JsonResponse
    {
        $raw = Str::lower(trim((string) $request->query('username', '')));

        if ($raw === '') {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => 'Enter a username to check.',
            ]);
        }

        if (strlen($raw) < 4 || strlen($raw) > 20 || ! preg_match('/^[a-zA-Z0-9_]+$/', $raw)) {
            return response()->json([
                'valid' => false,
                'available' => false,
                'message' => 'Use 4–20 characters: letters, numbers, or underscore only.',
            ]);
        }

        $taken = User::where('username', $raw)->exists();

        return response()->json([
            'valid' => true,
            'available' => ! $taken,
            'message' => $taken
                ? 'This username is already taken.'
                : 'This username is available.',
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

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username', ''))),
        ]);

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:4',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique(User::class),
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['nullable', 'in:customer,distributor'],
            'contact_number' => ['required', 'regex:/^09[0-9]{9}$/'],
            'address_line' => ['required_if:role,customer', 'nullable', 'string', 'max:500'],
            'city' => ['required_if:role,customer', 'nullable', 'string', Rule::in($cityKeys)],
            'barangay' => ['required_if:role,customer', 'nullable', 'string', 'max:100'],
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'terms_accepted' => ['required', 'accepted'],
            'latitude' => ['required_if:role,customer', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:role,customer', 'nullable', 'numeric', 'between:-180,180'],
        ], [
            'contact_number.regex' => 'Contact number must be 11 digits, start with 09 and contain only numbers.',
            'contact_number.required' => 'Contact number is required.',
            'address_line.required_if' => 'Complete Address is required.',
            'city.required_if' => 'City is required.',
            'barangay.required_if' => 'Barangay is required.',
            'latitude.required_if' => 'Please pin your exact location on the map.',
            'longitude.required_if' => 'Please pin your exact location on the map.',
            'terms_accepted.required' => 'You must accept the Terms and Conditions to create an account.',
            'terms_accepted.accepted' => 'You must accept the Terms and Conditions to create an account.',
        ]);

        $displayName = Str::title(str_replace('_', ' ', $request->username));

        $user = User::create([
            'name' => $displayName,
            'username' => $request->username,
            'email' => $request->email,
            'password' => $request->password,
            'phone_number' => $request->contact_number,
            'terms_accepted_at' => now(),
            'terms_version' => User::CURRENT_TERMS_VERSION,
        ]);

        $user->forceFill(['role' => $request->input('role', 'customer')])->save();

        if ($user->role === 'customer') {
            $zipCode = data_get(config('cavite.cities'), $request->city.'.zip', '');

            $user->addresses()->create([
                'label' => $request->label ?: 'Home',
                'recipient_name' => $request->recipient_name ?: $user->name,
                'contact_number' => $request->contact_number,
                'address_line' => $request->address_line,
                'city' => $request->city,
                'barangay' => $request->barangay,
                'province' => 'Cavite',
                'zip_code' => $zipCode,
                'is_default' => true,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        if ($user->role === 'distributor') {
            $request->session()->put('url.intended', route('owner.distributors.create', absolute: false));
        }

        return redirect()
            ->to(route('products.index', [], false))
            ->with(
                'info',
                'We sent a verification link to '.$user->email.'. Confirm your email to use checkout and your wallet — you can keep browsing meanwhile.'
            );
    }
}
