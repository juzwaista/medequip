<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * Enforce granular admin.* permissions for regular admin users.
     *
     * Super Admins bypass all checks. Regular admins must have the
     * named permission assigned via a Spatie role.
     *
     * Usage in routes: ->middleware('admin.permission:admin.applications.approve')
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthenticated.');
        }

        // Super admins always pass — no restrictions
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Regular admins: check via Spatie permission
        if (! $user->hasPermissionTo($permission)) {
            abort(403, "You do not have permission to perform this action ({$permission}).");
        }

        return $next($request);
    }
}
