<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTeamIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && class_exists(\Spatie\Permission\Models\Role::class)) {
            if ($user->role === 'distributor' || $user->role === 'staff') {
                if ($user->distributor_id) {
                    setPermissionsTeamId($user->distributor_id);
                }
            } else {
                // Platform admins operate outside of a team
                setPermissionsTeamId(null);
            }
        }

        return $next($request);
    }
}
