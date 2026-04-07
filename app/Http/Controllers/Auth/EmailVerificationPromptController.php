<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Laravel's verified middleware sends users here. We keep shopping UX: send them to the catalog
     * with a persistent banner (EmailVerificationBanner) instead of a dedicated verify page.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return redirect()->to(route('products.index', [], false));
    }
}
