<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 1. Super Admin always passes
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // 2. The "Distributor Validation" Logic
        if ($user->role === 'distributor') {
            $distributor = $user->distributor; 

            // If no profile exists OR it is still pending/rejected
            if (!$distributor || $distributor->status !== 'approved') {
                
                // EXACT route names from web.php that are safe to visit
                $safeRoutes = [
                    'owner.distributors.create', 
                    'owner.distributors.store', 
                    'owner.distributors.pending'
                ];

                // If they are on a safe route, LET THEM THROUGH
                if ($request->routeIs($safeRoutes)) {
                    return $next($request);
                }

                // If they are NOT on a safe route, route them based on status
                if ($distributor && $distributor->status === 'pending') {
                    return redirect()->route('owner.distributors.pending');
                }

                // Default fallback: send to application form
                return redirect()->route('owner.distributors.create')
                    ->with('info', 'Please complete your business application to continue.');
            }
        }

        // 3. Standard Role Check
        $allowedRoles = $roles;
        if (in_array('admin', $roles) && !in_array('super_admin', $roles)) {
            $allowedRoles[] = 'super_admin';
        }

        if (!in_array($user->role, $allowedRoles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}