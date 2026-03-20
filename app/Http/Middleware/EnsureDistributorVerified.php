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

        if ($user && $user->role === 'owner') {
            $distributor = $user->distributor;

            // If no distributor application at all, redirect to create
            if (!$distributor) {
                return redirect()->route('owner.distributor.create');
            }

            // If pending or rejected, block access to owner dashboard and redirect to pending
            // Except if the user is already on the pending route to avoid infinite loop
            if (in_array($distributor->status, ['pending', 'rejected']) && !$request->routeIs('owner.distributor.pending')) {
                return redirect()->route('owner.distributor.pending');
            }
        }

        return $next($request);
    }
}
