<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    /**
     * Display pending and recent withdrawal requests.
     */
    public function index()
    {
        $requests = WithdrawalRequest::with(['wallet.user', 'processor'])
            ->orderByRaw("FIELD(status, 'pending', 'processing', 'completed', 'rejected')")
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/Withdrawals/Index', [
            'requests' => $requests
        ]);
    }

    /**
     * Approve a withdrawal request.
     */
    public function approve(Request $request, $id)
    {
        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->withErrors(['message' => 'Only pending requests can be approved.']);
        }

        try {
            DB::transaction(function () use ($withdrawal) {
                $wallet = $withdrawal->wallet;

                if ($wallet->balance < $withdrawal->amount) {
                    throw new \Exception('Seller has insufficient wallet balance to fulfill this withdrawal.');
                }

                // Debit the wallet
                $wallet->debit(
                    $withdrawal->amount,
                    'withdrawal',
                    $withdrawal->id,
                    'Withdrawal approved and processed to bank: ' . $withdrawal->bank_name
                );

                // Update Request Status
                $withdrawal->update([
                    'status' => 'completed',
                    'processed_by' => Auth::id(),
                    'processed_at' => now(),
                ]);
            });

            return redirect()->back()->with('success', 'Withdrawal approved and funds deducted.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Reject a withdrawal request.
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|max:500'
        ]);

        $withdrawal = WithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending') {
            return redirect()->back()->withErrors(['message' => 'Only pending requests can be rejected.']);
        }

        $withdrawal->update([
            'status' => 'rejected',
            'notes' => 'Rejected: ' . $request->notes,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Withdrawal request rejected.');
    }
}
