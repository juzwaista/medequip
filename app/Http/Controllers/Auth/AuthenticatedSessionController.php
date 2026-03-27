<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): \Inertia\Response
    {
        return \Inertia\Inertia::render('Auth/Login', [
            'status' => session('status'),
            'error' => session('error'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user) {
            switch ($user->role) {
                case 'super_admin':
                case 'admin':
                    return redirect()->intended(route('admin.dashboard', absolute: false))
                    ->with('success','Logged in successfully!');
                case 'courier':
                    return redirect()->intended(route('courier.dashboard', absolute: false))
                    ->with('success','Logged in successfully!');
                case 'distributor':
                case 'staff':
                    // Check if distributor application is pending/rejected
                    if ($user->role === 'distributor') {
                        $distributor = $user->distributor;
                        if (!$distributor) {
                            return redirect()->route('owner.distributors.create')
                                ->with('info', 'Please complete your distributor registration.');
                        }
                        if (in_array($distributor->status, ['pending', 'rejected'])) {
                            return redirect()->route('owner.distributors.pending')
                                ->with('info', 'Your application is ' . $distributor->status . '.');
                        }
                    }
                    return redirect()->intended(route('owner.dashboard', absolute: false))
                        ->with('success', 'Logged in successfully!');
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/')
            ->with('status', 'You have been successfully logged out of MedEquip.');
    }
}
