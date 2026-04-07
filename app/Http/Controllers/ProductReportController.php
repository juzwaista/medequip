<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ProductReportController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $key = 'product-report:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->with('error', 'Too many reports. Please try again later.');
        }
        RateLimiter::hit($key, 3600);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:misleading,prohibited,counterfeit,spam,wrong_category,other'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        ProductReport::query()->create([
            'reporter_id' => $user->id,
            'product_id' => $product->id,
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'open',
        ]);

        return back()->with('success', 'Thanks — our team will review this listing.');
    }
}
