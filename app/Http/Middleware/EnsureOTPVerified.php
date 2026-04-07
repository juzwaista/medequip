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
                
                // If they are logged in but shouldn't be (stale session? manually typed URL?)
                // Force a re-login flow to generate a new OTP
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('error', 'Please log in to verify your identity with a security code.');
            }
        }

        return $next($request);
    }
}
