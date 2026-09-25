<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the shop-level role permissions (shop.manage-orders, shop.manage-inventory, ...) that
 * an owner assigns to staff through Role Templates. Those roles were never checked anywhere, so a
 * staff member limited to inventory could still open and act on orders.
 *
 * Shop owners always pass. For staff the permission is looked up in their shop's team (the team
 * id is set for every request by SetTeamIdMiddleware).
 *
 * Usage: ->middleware('shop.permission:shop.manage-orders')
 */
class ShopPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthenticated.');
        }

        if ($user->role !== 'staff') {
            return $next($request);
        }

        try {
            $allowed = $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            $allowed = false;
        }

        if (! $allowed) {
            abort(403, 'Your role does not include access to this area. Ask the shop owner to update your role.');
        }

        return $next($request);
    }
}
