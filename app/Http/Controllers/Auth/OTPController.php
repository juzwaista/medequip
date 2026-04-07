<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\LoginOTP;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OTPController extends Controller
{
    /**
     * Show the OTP verification screen.
     */
    public function show(Request $request): \Inertia\Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        // Redirect if not an admin or already verified
        if (!$user || !in_array($user->role, ['admin', 'super_admin'])) {
            return redirect()->route('landing');
        }

        if ($request->session()->get('login.otp_verified', false)) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return Inertia::render('Auth/VerifyOTP', [
            'status' => session('status'),
            'error' => session('error'),
            'info' => session('info'),
        ]);
    }

    /**
     * Handle the OTP verification request.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if OTP is correct and not expired
        if ($user->login_otp === $request->otp && 
            $user->login_otp_expires_at && 
            now()->isBefore($user->login_otp_expires_at)) {
            
            // Clear OTP fields
            $user->update([
                'login_otp' => null,
                'login_otp_expires_at' => null,
            ]);

            // Mark session as OTP verified
            $request->session()->put('login.otp_verified', true);

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Security verification successful. Welcome back, ' . $user->name . '!');
        }

        return back()->withErrors(['otp' => 'The security code is incorrect or has expired.']);
    }

    /**
     * Resend the OTP verification code.
     */
    public function resend(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'login_otp' => $otp,
            'login_otp_expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new LoginOTP($otp));

        return back()->with('info', 'A new security code has been sent to your email.');
    }
}
