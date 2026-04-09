<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Only exempt the PayMongo webhook — it POSTs without a session token.
        // All other routes use the CSRF token synced via Inertia props + axios interceptors.
        $middleware->validateCsrfTokens(except: [
            'payments/webhook',
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'otp' => \App\Http\Middleware\EnsureOTPVerified::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Never run destructive maintenance on local/staging unless you opt in.
        // Mis-set APP_ENV=production on a dev machine + schedule:work could delete deactivated accounts.
        $schedule->command('accounts:purge-deactivated')
            ->daily()
            ->environments(['production']);

        $schedule->command('medequip:alert-doc-expiry')
            ->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
