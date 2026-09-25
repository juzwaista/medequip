<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Super Admin always passes
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // 2. Standard Role Check
        //
        // Distributor/staff application, approval, suspension, and ban status are handled by
        // EnsureDistributorVerified, which always runs alongside this middleware on every route
        // gated by role:distributor (see routes/web.php) — this used to also be checked here,
        // but that duplicate, simpler check could short-circuit the request before
        // EnsureDistributorVerified's more complete logic (e.g. the banned-account logout) ever
        // ran, so it was removed in favor of a single source of truth.
        $allowedRoles = $roles;
        if (in_array('admin', $roles) && ! in_array('super_admin', $roles)) {
            $allowedRoles[] = 'super_admin';
        }

        if (! in_array($user->role, $allowedRoles)) {
            return redirect('/')->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
