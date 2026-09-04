<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOTPVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Only enforce for admin/super_admin
        if ($user && in_array($user->role, ['admin', 'super_admin'])) {
            // Check if OTP was verified for this session
            if (! $request->session()->get('login.otp_verified', false)) {
                
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $user->update([
                    'login_otp' => $otp,
                    'login_otp_expires_at' => now()->addMinutes(15),
                ]);
                $user->notify(new \App\Notifications\LoginOTP($otp));

                return redirect()->route('admin.otp.verify')
                    ->with('info', 'Your security session expired. Please verify your identity again with a new security code.');
            }
        }

        return $next($request);
    }
}
