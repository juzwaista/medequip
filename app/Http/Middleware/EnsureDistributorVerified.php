<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDistributorVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->role === 'distributor') {
            $distributor = $user->distributor;

            // If no distributor application at all, redirect to create
            if (!$distributor) {
                return redirect()->route('owner.distributors.create');
            }

            // If banned, force logout immediately
            if ($distributor->status === 'banned') {
                \Illuminate\Support\Facades\Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')
                    ->with('error', 'Your distributor account has been permanently banned.');
            }

            // If suspended, allow them to stay logged in but show warning (handled in layout)
            // No longer forcing logout here, but we block specific routes
            if ($distributor->is_suspended) {
                // Restricted routes while suspended
                $restrictedSuspended = [
                    'owner.inventory.*',
                    'owner.pos.*',
                    'owner.staff.*',
                    'owner.profile.*',
                    'owner.sales.*',
                    'owner.dss.*',
                    'owner.distributors.branches.*',
                ];

                if ($request->routeIs(...$restrictedSuspended)) {
                    return redirect()->route('owner.dashboard')
                        ->with('error', 'Your account is currently suspended. You are restricted to processing existing orders only.');
                }
            }

            // If pending or rejected, block access and redirect to the pending page
            // Allow access to pending/create routes to avoid infinite loops
            $allowedRoutes = ['owner.distributors.pending', 'owner.distributors.create', 'owner.distributors.store'];
            if (in_array($distributor->status, ['pending', 'rejected', null]) && !$request->routeIs(...$allowedRoutes)) {
                // null status = distributor was reset for re-application, send to create form
                if (is_null($distributor->status)) {
                    return redirect()->route('owner.distributors.create');
                }
                return redirect()->route('owner.distributors.pending');
            }
        }

        return $next($request);
    }
}
