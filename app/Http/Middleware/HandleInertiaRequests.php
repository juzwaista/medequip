<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $distributorStatus = null;
        $suspendedUntil = null;
        $suspensionReason = null;
        $warningReason = null;
        $warningMessage = null;
        if ($user && $user->role === 'distributor') {
            $distributorStatus = $user->distributor?->status;
            $suspendedUntil = $user->distributor?->suspended_until?->timestamp;
            $suspensionReason = $user->distributor?->suspension_reason;
            $warningReason = $user->distributor?->warning_reason;
            $warningMessage = $user->distributor?->warning_message;
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id'                 => $user->id,
                    'name'               => $user->name,
                    'email'              => $user->email,
                    'role'               => $user->role,
                    'distributor_status' => $distributorStatus,
                    'suspended_until'    => $suspendedUntil,
                    'suspension_reason'  => $suspensionReason,
                    'warning_reason'     => $warningReason,
                    'warning_message'    => $warningMessage,
                ] : null,
            ],
            'needsTermsAcceptance' => $user ? !$user->hasAcceptedTerms() : false,
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
                'info'    => fn() => $request->session()->get('info'),
                'warning' => fn() => $request->session()->get('warning'),
                'status'  => fn() => $request->session()->get('status'),
            ],
        ]);
    }
}
