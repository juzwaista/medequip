<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        Log::info('[ProfileController] Account settings page accessed', [
            'user_id' => $request->user()->id,
            'user_type' => $request->user()->user_type
        ]);

        return Inertia::render('Settings/AccountSettings', [
            'user' => $request->user()
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        
        Log::info('[ProfileController] Profile update initiated', [
            'user_id' => $user->id,
            'name_changed' => $request->name !== $user->name,
            'email_changed' => $request->email !== $user->email
        ]);

        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            Log::info('[ProfileController] Email changed, verification reset', [
                'user_id' => $user->id,
                'old_email' => $user->getOriginal('email'),
                'new_email' => $user->email
            ]);
        }

        $user->save();

        Log::info('[ProfileController] Profile updated successfully', [
            'user_id' => $user->id
        ]);

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

        Log::info('[ProfileController] Password update initiated', [
            'user_id' => $user->id
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        Log::info('[ProfileController] Password updated successfully', [
            'user_id' => $user->id
        ]);

        return Redirect::back()->with('success', 'Password updated successfully');
    }

    /**
     * Deactivate the user's account.
     * The account is permanently deleted after 30 days of inactivity by the scheduler.
     */
    public function deactivate(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Log::warning('[ProfileController] Account deactivation initiated', [
            'user_id'    => $user->id,
            'user_email' => $user->email,
            'user_type'  => $user->user_type,
        ]);

        $user->update(['deactivated_at' => now()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('[ProfileController] Account deactivated. Will be permanently deleted in 30 days.', [
            'user_id' => $user->id,
        ]);

        return Redirect::to('/')
            ->with('success', 'Your account has been deactivated. It will be permanently deleted after 30 days unless you log back in to reactivate it.');
    }

    /**
     * Reactivate a deactivated account on successful login.
     * Called from the LoginController or middleware.
     */
    public function reactivate(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->deactivated_at) {
            $user->update(['deactivated_at' => null]);
            Log::info('[ProfileController] Account reactivated', ['user_id' => $user->id]);
        }
        return Redirect::back()->with('success', 'Your account has been reactivated.');
    }
}

