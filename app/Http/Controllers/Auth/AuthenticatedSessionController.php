<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Auth/Login', [
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Block banned users immediately after authentication
        if ($user && ! is_null($user->banned_at)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your account has been suspended. Please contact support for assistance.');
        }

        // Check for Admin/SuperAdmin OTP
        if ($user && in_array($user->role, ['admin', 'super_admin'])) {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->update([
                'login_otp' => $otp,
                'login_otp_expires_at' => now()->addMinutes(15),
            ]);

            // Notify user
            $user->notify(new \App\Notifications\LoginOTP($otp));

            // Log out user for now (the session will keep their ID for verification)
            $userId = $user->id;
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Put user ID back in session and flag for OTP
            $request->session()->put('login.otp_user_id', $userId);
            $request->session()->put('login.otp_expires_at', now()->addMinutes(15)->timestamp);

            return redirect()->route('admin.otp.verify')
                ->with('info', 'A security code has been sent to your email.');
        }

        if ($user) {
            switch ($user->role) {
                case 'super_admin':
                case 'admin':
                    return redirect()->intended(route('admin.dashboard', absolute: false))
                        ->with('success', 'Logged in successfully!');
                case 'courier':
                    // Always land couriers on their app; ignore stale url.intended from public pages.
                    return redirect()->route('courier.dashboard')
                        ->with('success', 'Logged in successfully!');
                case 'distributor':
                case 'staff':
                    // Check if distributor application is pending/rejected
                    if ($user->role === 'distributor') {
                        $distributor = $user->distributor;
                        if (! $distributor) {
                            return redirect()->route('owner.distributors.create')
                                ->with('info', 'Please complete your distributor registration.');
                        }
                        if (in_array($distributor->status, ['pending', 'rejected'])) {
                            return redirect()->route('owner.distributors.pending')
                                ->with('info', 'Your application is '.$distributor->status.'.');
                        }
                    }

                    return redirect()->intended(route('owner.dashboard', absolute: false))
                        ->with('success', 'Logged in successfully!');
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')
            ->with('status', 'You have been successfully logged out of MedEquip.');
    }
}
