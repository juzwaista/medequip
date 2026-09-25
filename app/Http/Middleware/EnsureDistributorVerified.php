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

        if (! $user || ! in_array($user->role, ['distributor', 'staff'], true)) {
            return $next($request);
        }

        $isOwner = $user->role === 'distributor';
        // Staff act on behalf of their employer's shop, so every check below is against
        // that shop's Distributor row rather than one the staff account owns itself.
        $distributor = $isOwner ? $user->distributor : $user->employer;

        // Owners with no application yet go to the application form; staff can't create one,
        // and a staff account with no employer at all is in a broken state — sign them out.
        if (! $distributor) {
            if ($isOwner) {
                return redirect()->route('owner.distributors.create');
            }

            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your staff account is not linked to an active shop.');
        }

        // If the shop is banned, force logout immediately — for the owner and any staff alike.
        if ($distributor->status === 'banned') {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your distributor account has been permanently banned.');
        }

        // If suspended, allow them to stay logged in but show warning (handled in layout)
        // No longer forcing logout here, but we block specific routes — for owner and staff.
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

        // Not yet approved: the owner can complete the application; staff have no such flow
        // and would otherwise be stuck on a portal that isn't usable yet, so send them away.
        if (in_array($distributor->status, ['pending', 'rejected', null], true)) {
            if (! $isOwner) {
                return redirect('/products')
                    ->with('error', "Your employer's shop is not currently active. Please contact your business owner.");
            }

            // Allow access to pending/create routes to avoid infinite loops
            $allowedRoutes = ['owner.distributors.pending', 'owner.distributors.create', 'owner.distributors.store'];
            if (! $request->routeIs(...$allowedRoutes)) {
                // null status = distributor was reset for re-application, send to create form
                if (is_null($distributor->status)) {
                    return redirect()->route('owner.distributors.create');
                }

                return redirect()->route('owner.distributors.pending');
            }
        }

        // Approved owners: short shop setup (slug + description + optional branding) before full
        // portal access. Staff don't own this step, so they're left alone here.
        if ($isOwner
            && $distributor->status === 'approved'
            && $distributor->shop_profile_onboarding_completed_at === null) {
            $setupRoutes = ['owner.shop.setup', 'owner.shop.setup.store', 'owner.profile.checkSlug', 'logout'];
            if (! $request->routeIs(...$setupRoutes)) {
                return redirect()->route('owner.shop.setup');
            }
        }

        return $next($request);
    }
}
