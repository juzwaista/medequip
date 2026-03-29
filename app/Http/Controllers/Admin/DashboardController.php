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

        // DSS Risk Assessment Engine
        $atRiskDistributors = [];
        $activeDistributors = Distributor::where('status', 'approved')->where('status', '!=', 'banned')->with('owner')->get();
        $now = \Carbon\Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        foreach ($activeDistributors as $distributor) {
            $reasons = [];
            $riskScore = 0;

            // Metric 1: Cancellation Rate
            $recentOrders = Order::where('distributor_id', $distributor->id)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->get();
            $totalRecent = $recentOrders->count();
            if ($totalRecent > 0) {
                $cancelledCount = $recentOrders->whereIn('status', ['cancelled', 'rejected'])->count();
                $cancellationRate = $cancelledCount / $totalRecent;
                if ($cancellationRate > 0.15) {
                    $ratePct = round($cancellationRate * 100);
                    $reasons[] = "High Cancellation Rate ({$ratePct}% in last 30 days)";
                    $riskScore += 2;
                }
            }

            // Metric 2: Fulfillment Delay (Orders pending > 48h)
            $stalePendingCount = Order::where('distributor_id', $distributor->id)
                ->where('status', 'pending')
                ->where('created_at', '<', $now->copy()->subHours(48))
                ->count();
            if ($stalePendingCount > 0) {
                $reasons[] = "{$stalePendingCount} order(s) pending approval for over 48 hours";
                $riskScore += ($stalePendingCount >= 5) ? 3 : 1;
            }

            // Metric 3: Total Stockout
            $activeProductsCount = Product::where('distributor_id', $distributor->id)->where('is_active', true)->count();
            if ($activeProductsCount > 0) {
                $totalInventory = \App\Models\Inventory::whereHas('product', function($q) use ($distributor) {
                    $q->where('distributor_id', $distributor->id)->where('is_active', true);
                })->sum('quantity');

                if ($totalInventory == 0) {
                    $reasons[] = "All active products are out of stock";
                    $riskScore += 1;
                }
            }

            if ($riskScore > 0) {
                $riskLevel = 'Medium';
                
                if ($riskScore >= 4) {
                    $riskLevel = 'Critical';
                } elseif ($riskScore >= 2) {
                    $riskLevel = 'High';
                }

                $atRiskDistributors[] = [
                    'id' => $distributor->id,
                    'company_name' => $distributor->company_name,
                    'owner_email' => $distributor->owner?->email ?? '—',
                    'contact_number' => $distributor->contact_number,
                    'reasons' => $reasons,
                    'risk_level' => $riskLevel,
                    'is_suspended' => !is_null($distributor->suspended_until) && $distributor->suspended_until->isFuture(),
                    'suspended_until' => $distributor->suspended_until?->format('M d, Y h:i A'),
                ];
            }
        }

        // Sort by risk descending
        usort($atRiskDistributors, function($a, $b) {
            $valA = $a['risk_level'] === 'Critical' ? 3 : ($a['risk_level'] === 'High' ? 2 : 1);
            $valB = $b['risk_level'] === 'Critical' ? 3 : ($b['risk_level'] === 'High' ? 2 : 1);
            return $valB <=> $valA;
        });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'pendingDistributors' => $pendingDistributors,
            'recentActivity' => $recentActivity,
            'atRiskDistributors' => $atRiskDistributors,
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

    /**
     * Suspend a distributor
     */
    public function suspendDistributor(Request $request, $id)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'days' => 'required|integer|min:1|max:365',
        ]);

        $distributor->update([
            'suspended_until' => \Carbon\Carbon::now()->addDays($validated['days']),
            'suspension_reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', "Distributor suspended for {$validated['days']} days.");
    }

    /**
     * Ban a distributor
     */
    public function banDistributor(Request $request, $id)
    {
        $distributor = Distributor::findOrFail($id);
        
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $distributor->update([
            'status' => 'banned',
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Distributor has been permanently banned.');
    }

    /**
     * Issue a warning to a distributor
     */
    public function warnDistributor(Request $request, $id)
    {
        $distributor = Distributor::findOrFail($id);
        
        $validated = $request->validate([
            'preset_reason' => 'required|string',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $distributor->update([
            'warning_reason' => $validated['preset_reason'],
            'warning_message' => $validated['custom_message'],
        ]);

        $message = "Warning issued to {$distributor->company_name} for {$validated['preset_reason']}.";

        return redirect()->route('admin.dashboard')
            ->with('success', $message);
    }
    /**
     * Securely serve distributor compliance documents to admins
     */
    public function viewDocument($path)
    {
        // Enforce boundary to distributor_documents
        if (!str_starts_with($path, 'distributor_documents/')) {
            abort(403, 'Unauthorized Access');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (!$disk->exists($path)) {
            abort(404, 'Document not found');
        }

        return response()->file($disk->path($path));
    }
}
