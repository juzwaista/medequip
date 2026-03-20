<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            abort(403);
        }

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // If the role requires 'admin', then 'super_admin' naturally passes.
        $allowedRoles = $roles;
        if (in_array('admin', $roles) && !in_array('super_admin', $roles)) {
            $allowedRoles[] = 'super_admin';
        }

        if (!in_array($user->role, $allowedRoles)) {
            abort(403);
        }

        return $next($request);
    }
}
