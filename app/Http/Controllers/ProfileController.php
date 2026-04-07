<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        return Inertia::render('Settings/AccountSettings', [
            'user' => $request->user(),
            'status' => session('status'),
            'info' => session('info'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Handle Email Change with Verification
        if ($validated['email'] !== $user->email) {
            $newEmail = $validated['email'];
            
            // Generate OTP for the new email
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->update([
                'pending_email' => $newEmail,
                'login_otp' => $otp,
                'login_otp_expires_at' => now()->addMinutes(15),
            ]);

            // Notify the NEW email address specifically
            \Illuminate\Support\Facades\Notification::route('mail', $newEmail)
                ->notify(new \App\Notifications\LoginOTP($otp));

            // Don't update the primary email yet
            unset($validated['email']);
        }

        $user->fill($validated);
        $user->save();

        if ($user->pending_email) {
            return Redirect::route('profile.edit')->with('info', 'A verification code has been sent to ' . $user->pending_email . '. Please verify to complete the change.');
        }

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

        $user->update([
            'password' => $validated['password'],
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
            'user_id' => $user->id,
            'user_email' => $user->email,
        ]);

        $user->update(['deactivated_at' => now()]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
        }

        return Redirect::back()->with('success', 'Your account has been reactivated.');
    }

    /**
     * Verify the security code for an email change.
     */
    public function verifyEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        // Check if OTP matches and pending email exists
        if ($user->pending_email && 
            $user->login_otp === $request->otp && 
            $user->login_otp_expires_at && 
            now()->isBefore($user->login_otp_expires_at)) {
            
            $oldEmail = $user->email;
            $newEmail = $user->pending_email;

            $user->update([
                'email' => $newEmail,
                'pending_email' => null,
                'login_otp' => null,
                'login_otp_expires_at' => null,
                'email_verified_at' => now(), // Assume verified since they entered the OTP
            ]);

            \Illuminate\Support\Facades\Log::info('[ProfileController] Email verified and changed', [
                'user_id' => $user->id,
                'from' => $oldEmail,
                'to' => $newEmail,
            ]);

            return Redirect::route('profile.edit')->with('success', 'Email updated successfully to ' . $newEmail);
        }

        return Redirect::back()->withErrors(['otp' => 'The security code is incorrect or has expired.']);
    }
}
