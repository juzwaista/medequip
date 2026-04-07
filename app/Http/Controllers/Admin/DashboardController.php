<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\AdminModerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $stats = [
            'totalUsers' => User::count(),
            'totalDistributors' => Distributor::where('status', 'approved')->count(),
            'pendingVerifications' => Distributor::where('status', 'pending')->count(),
            'totalProducts' => Product::count(),
            'totalOrders' => Order::count(),
        ];

        $financial = [
            'totalRevenue' => (float) Payment::where('status', 'verified')->sum('amount'),
            'platformFees' => (float) Payment::where('status', 'verified')->sum('platform_fee_amount'),
            'escrowHeld' => (float) Payment::where('status', 'verified')->where('escrow_status', 'held')->sum('amount'),
            'ordersThisMonth' => Order::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
            'newUsersThisMonth' => User::where('created_at', '>=', $now->copy()->startOfMonth())->count(),
        ];

        $orderTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $orderTrend[] = [
                'label' => $day->format('M d'),
                'count' => Order::whereDate('created_at', $day->toDateString())->count(),
            ];
        }

        $recentOrders = Order::with('customer:id,name', 'distributor:id,company_name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer' => $o->customer?->name ?? '—',
                'shop' => $o->distributor?->company_name ?? '—',
                'status' => $o->status,
                'total' => (float) $o->total_amount,
                'created_at' => $o->created_at->diffForHumans(),
            ]);

        $recentActivity = Distributor::orderByDesc('created_at')
            ->with('owner')
            ->limit(6)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'company_name' => $d->company_name,
                'user_name' => $d->owner?->name ?? '—',
                'status' => $d->status,
                'created_at' => $d->created_at->diffForHumans(),
            ]);

        $atRiskDistributors = $this->buildRiskAssessment($thirtyDaysAgo, $now);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'financial' => $financial,
            'orderTrend' => $orderTrend,
            'recentOrders' => $recentOrders,
            'recentActivity' => $recentActivity,
            'atRiskDistributors' => $atRiskDistributors,
        ]);
    }

    private function buildRiskAssessment(Carbon $since, Carbon $now): array
    {
        $result = [];
        $distributors = Distributor::where('status', 'approved')
            ->with('owner')
            ->get();

        foreach ($distributors as $dist) {
            $reasons = [];
            $score = 0;

            $orders = Order::where('distributor_id', $dist->id)
                ->where('created_at', '>=', $since)
                ->get();

            if ($orders->count() > 0) {
                $cancelRate = $orders->whereIn('status', ['cancelled', 'rejected'])->count() / $orders->count();
                if ($cancelRate > 0.15) {
                    $reasons[] = 'High cancellation rate ('.round($cancelRate * 100).'%)';
                    $score += 2;
                }
            }

            $stale = Order::where('distributor_id', $dist->id)
                ->where('status', 'pending')
                ->where('created_at', '<', $now->copy()->subHours(48))
                ->count();
            if ($stale > 0) {
                $reasons[] = "{$stale} order(s) pending over 48 h";
                $score += $stale >= 5 ? 3 : 1;
            }

            $activeProducts = Product::where('distributor_id', $dist->id)->where('is_active', true)->count();
            if ($activeProducts > 0) {
                $inv = \App\Models\Inventory::whereHas('product', fn ($q) => $q->where('distributor_id', $dist->id)->where('is_active', true))->sum('quantity');
                if ($inv == 0) {
                    $reasons[] = 'All products out of stock';
                    $score += 1;
                }
            }

            if ($score > 0) {
                $level = $score >= 4 ? 'Critical' : ($score >= 2 ? 'High' : 'Medium');
                $result[] = [
                    'id' => $dist->id,
                    'company_name' => $dist->company_name,
                    'owner_email' => $dist->owner?->email ?? '—',
                    'contact_number' => $dist->contact_number,
                    'reasons' => $reasons,
                    'risk_level' => $level,
                    'is_suspended' => $dist->suspended_until?->isFuture() ?? false,
                    'suspended_until' => $dist->suspended_until?->format('M d, Y h:i A'),
                ];
            }
        }

        usort($result, fn ($a, $b) => ['Critical' => 3, 'High' => 2, 'Medium' => 1][$b['risk_level']] <=> ['Critical' => 3, 'High' => 2, 'Medium' => 1][$a['risk_level']]);

        return $result;
    }

    /**
     * Approve a distributor
     */
    public function approveDistributor($id, AdminModerationService $moderation)
    {
        $distributor = Distributor::findOrFail($id);
        $moderation->approveDistributor(request()->user(), $distributor);

        return redirect()->back()
            ->with('success', 'Distributor approved successfully.');
    }

    /**
     * Reject a distributor
     */
    public function rejectDistributor(Request $request, $id, AdminModerationService $moderation)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $moderation->rejectDistributor($request->user(), $distributor, $validated['reason'] ?? null);

        return redirect()->back()
            ->with('success', 'Distributor rejected.');
    }

    /**
     * Suspend a distributor
     */
    public function suspendDistributor(Request $request, $id, AdminModerationService $moderation)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
            'days' => 'required|integer|min:1|max:365',
        ]);

        $moderation->suspendDistributor($request->user(), $distributor, (int) $validated['days'], $validated['reason']);

        return redirect()->back()
            ->with('success', "Distributor suspended for {$validated['days']} days.");
    }

    /**
     * Lift suspension of a distributor
     */
    public function liftSuspension(Request $request, $id, AdminModerationService $moderation)
    {
        \Illuminate\Support\Facades\Log::info("Admin attempt to lift suspension for Distributor ID: {$id}");

        $distributor = Distributor::findOrFail($id);

        $moderation->liftDistributorSuspension($request->user(), $distributor);

        return redirect()->back()
            ->with('success', "Suspension lifted for {$distributor->company_name}.");
    }

    /**
     * Ban a distributor
     */
    public function banDistributor(Request $request, $id, AdminModerationService $moderation)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $moderation->banDistributor($request->user(), $distributor, $validated['reason']);

        return redirect()->back()
            ->with('success', 'Distributor has been permanently banned.');
    }

    /**
     * Issue a warning to a distributor
     */
    public function warnDistributor(Request $request, $id, AdminModerationService $moderation)
    {
        $distributor = Distributor::findOrFail($id);

        $validated = $request->validate([
            'preset_reason' => ['required', 'string', Rule::in(AdminModerationService::DISTRIBUTOR_WARN_PRESETS)],
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $moderation->warnDistributor(
            $request->user(),
            $distributor,
            $validated['preset_reason'],
            $validated['custom_message'] ?? null
        );

        $message = "Warning issued to {$distributor->company_name} for {$validated['preset_reason']}.";

        return redirect()->back()
            ->with('success', $message);
    }

    /**
     * Broadcast a system-wide announcement to all users.
     */
    public function broadcastAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
        ]);

        $admin = $request->user();
        $title = $validated['title'];
        $message = $validated['message'];

        // Batch processing to avoid server timeout/memory issues
        User::chunk(100, function ($users) use ($title, $message, $admin) {
            foreach ($users as $user) {
                $user->notify(new \App\Notifications\SystemAnnouncement($title, $message, $admin->name));
            }
        });

        // Also log this for audit
        \Illuminate\Support\Facades\Log::info("Global Announcement Broadcast by Admin: {$admin->name}", [
            'admin_id' => $admin->id,
            'title' => $title,
        ]);

        return redirect()->back()
            ->with('success', 'Announcement has been broadcasted to all users successfully.');
    }

    /**
     * Securely serve distributor compliance documents to admins
     */
    public function viewDocument($path)
    {
        // Enforce boundary to distributor_documents
        if (! str_starts_with($path, 'distributor_documents/')) {
            abort(403, 'Unauthorized Access');
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');

        if (! $disk->exists($path)) {
            abort(404, 'Document not found');
        }

        return response()->file($disk->path($path));
    }
}
