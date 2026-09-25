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

        // Regular admins: check via Spatie permission. hasPermissionTo() throws
        // PermissionDoesNotExist if the permission row itself was never seeded (e.g. this
        // middleware references a name that hasn't been run through the permissions seeder
        // yet) — treat that the same as "not granted" (403) rather than letting it surface
        // as an unhandled 500.
        try {
            $hasPermission = $user->hasPermissionTo($permission);
        } catch (\Spatie\Permission\Exceptions\PermissionDoesNotExist) {
            $hasPermission = false;
        }

        if (! $hasPermission) {
            abort(403, "You do not have permission to perform this action ({$permission}).");
        }

        return $next($request);
    }
}
