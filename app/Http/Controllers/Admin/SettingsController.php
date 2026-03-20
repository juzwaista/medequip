<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\SystemSetting;

class SettingsController extends Controller
{
    /**
     * Display the settings configuration panel.
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // Enforce Super Admin only
        if (!$user->is_super_admin) {
            abort(403, 'Only Super Admins can access global settings.');
        }

        $platformFeeRate = SystemSetting::getSetting('platform_fee_rate', config('services.platform.fee_rate', 0.05));
        
        // Let's display it as a percentage on the frontend safely (e.g. 5)
        $platformFeePercent = $platformFeeRate * 100;

        return Inertia::render('Admin/Settings/Index', [
            'platformFeePercent' => $platformFeePercent,
        ]);
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user->is_super_admin) {
            abort(403, 'Only Super Admins can update global settings.');
        }

        $request->validate([
            'platform_fee_percent' => 'required|numeric|min:0|max:100',
        ]);

        $decimalRate = $request->platform_fee_percent / 100;

        SystemSetting::updateOrCreate(
            ['key' => 'platform_fee_rate'],
            [
                'value' => (string) $decimalRate,
                'type' => 'decimal',
                'description' => 'Global platform commission rate for completed sales.',
            ]
        );

        return redirect()->back()->with('success', 'Global Settings updated successfully.');
    }
}
