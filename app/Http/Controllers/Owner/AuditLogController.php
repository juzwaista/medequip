<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * Display distributor audit logs
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $distributor = $user->distributor;

        if (!$distributor) {
            abort(404, 'Distributor profile not found.');
        }

        $query = AuditLog::with('user')
            ->whereHas('user', function ($q) use ($distributor) {
                $q->where('distributor_id', $distributor->id);
            });

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);
        $staffMembers = \App\Models\User::where('distributor_id', $distributor->id)->get(['id', 'name', 'role']);

        return Inertia::render('Owner/Audit/Index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id']),
            'staffMembers' => $staffMembers,
        ]);
    }
}
