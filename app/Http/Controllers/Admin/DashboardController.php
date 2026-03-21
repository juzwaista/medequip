<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard
     */
    public function index()
    {
        // Pending distributor verifications
        $pendingDistributors = Distributor::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->with('owner')
            ->get();

        // Platform stats
        $stats = [
            'totalDistributors'  => Distributor::where('status', 'approved')->count(),
            'pendingDistributors' => Distributor::where('status', 'pending')->count(),
            'totalProducts'      => Product::count(),
            'totalOrders'        => Order::count(),
            'totalUsers'         => User::count(),
        ];

        // Recent activity (last 10 distributor registrations)
        $recentActivity = Distributor::orderBy('created_at', 'desc')
            ->with('owner')
            ->limit(10)
            ->get()
            ->map(function ($dist) {
                return [
                    'id'           => $dist->id,
                    'company_name' => $dist->company_name,
                    'user_name'    => $dist->owner?->name ?? '—',
                    'status'       => $dist->status,
                    'created_at'   => $dist->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'pendingDistributors' => $pendingDistributors,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Approve a distributor
     */
    public function approveDistributor($id)
    {
        $distributor = Distributor::findOrFail($id);
        $distributor->update(['status' => 'approved']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Distributor approved successfully.');
    }

    /**
     * Reject a distributor
     */
    public function rejectDistributor(Request $request, $id)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $distributor->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Distributor rejected.');
    }
}
