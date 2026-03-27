<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\DssAlert;
use App\Models\DssDistributorSettings;
use App\Models\DssReorderRecommendation;
use App\Services\DssEngineService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DssController extends Controller
{
    public function __construct(private DssEngineService $engine) {}

    public function index()
    {
        $distributor = auth()->user()->distributor;
        if (!$distributor) {
            abort(403, 'Distributor profile is required.');
        }

        $this->engine->syncForDistributor($distributor->id);
        $insights = $this->engine->getInsights($distributor->id);

        return Inertia::render('Owner/DSS/Index', [
            'insights' => $insights,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $distributor = auth()->user()->distributor;
        if (!$distributor) {
            abort(403);
        }

        $validated = $request->validate([
            'low_stock_threshold_days' => 'required|integer|min:1|max:90',
            'expiry_warning_days' => 'required|integer|min:1|max:365',
            'dead_stock_days' => 'required|integer|min:1|max:365',
            'enable_auto_alerts' => 'required|boolean',
        ]);

        DssDistributorSettings::updateOrCreate(
            ['distributor_id' => $distributor->id],
            $validated
        );

        return back()->with('success', 'DSS settings updated successfully.');
    }

    public function markAlertRead(DssAlert $alert)
    {
        $distributor = auth()->user()->distributor;
        if (!$distributor || $alert->distributor_id !== $distributor->id) {
            abort(403);
        }

        $alert->markAsRead();
        return back()->with('success', 'Alert marked as read.');
    }

    public function actionRecommendation(DssReorderRecommendation $recommendation)
    {
        $distributor = auth()->user()->distributor;
        if (!$distributor || $recommendation->distributor_id !== $distributor->id) {
            abort(403);
        }

        $recommendation->markAsActioned();
        return back()->with('success', 'Recommendation marked as actioned.');
    }
}

