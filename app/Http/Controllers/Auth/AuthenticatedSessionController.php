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

        // Handle Admin/SuperAdmin OTP Security
        if ($user && in_array($user->role, ['admin', 'super_admin'])) {
            // Only trigger if they haven't verified for this session yet
            if (! $request->session()->get('login.otp_verified', false)) {
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
                $user->update([
                    'login_otp' => $otp,
                    'login_otp_expires_at' => now()->addMinutes(15),
                ]);

                $user->notify(new \App\Notifications\LoginOTP($otp));

                return redirect()->route('admin.otp.verify')
                    ->with('info', 'For your security, please enter the code sent to your email.');
            }
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
