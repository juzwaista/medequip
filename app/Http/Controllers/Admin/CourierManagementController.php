<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Courier;
use App\Models\Delivery;

class CourierManagementController extends Controller
{
    /**
     * List all couriers and their basic stats.
     */
    public function index()
    {
        $couriers = Courier::with('user')
            ->withCount(['deliveries as active_deliveries_count' => function ($query) {
                $query->whereIn('status', ['pending', 'picked_up', 'in_transit']);
            }])
            ->withCount(['deliveries as completed_deliveries_count' => function ($query) {
                $query->where('status', 'delivered');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Couriers/Index', [
            'couriers' => $couriers
        ]);
    }

    /**
     * View all deliveries platform-wide.
     */
    public function deliveries(Request $request)
    {
        $query = Delivery::with(['courier.user', 'order.customer']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deliveries = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Couriers/Deliveries', [
            'deliveries' => $deliveries,
            'filters' => $request->only('status')
        ]);
    }
}
