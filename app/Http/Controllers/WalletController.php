<?php

namespace App\Http\Controllers;

use App\Services\PayMongoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function __construct(private PayMongoService $paymongo) {}

    public function index()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = $user->wallet()->create();
        }

        $wallet->load(['transactions' => function($query) {
            $query->latest()->limit(50);
        }]);

        return Inertia::render('Wallet/Index', [
            'wallet'       => collect($wallet)->only(['id', 'balance', 'status']),
            'transactions' => $wallet->transactions,
        ]);
    }

    /**
     * Initiate a wallet top-up via PayMongo checkout.
     * Stores the pending amount in session, then redirects to PayMongo payment page.
     */
    public function topup(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:50000',
        ]);

        /** @var \App\Models\User $user */
        $user   = auth()->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            $wallet = $user->wallet()->create();
        }

        $amountCentavos = (int) round($validated['amount'] * 100);

        $topupToken = Str::uuid()->toString();

        // Store pending top-up in session so the callback can credit it
        session([
            'wallet_topup_amount'    => $validated['amount'],
            'wallet_topup_wallet_id' => $wallet->id,
            'wallet_topup_token'     => $topupToken,
        ]);

        try {
            $session = $this->paymongo->createGenericCheckoutSession(
                description:     'Wallet Top-up — ₱' . number_format($validated['amount'], 2),
                amountCentavos:  $amountCentavos,
                successUrl:      route('wallet.topup.success') . '?token=' . $topupToken,
                cancelUrl:       route('wallet.topup.cancel'),
                metadata:        [
                    'user_id'   => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount'    => $validated['amount'],
                ]
            );

            Log::info('[WalletController] Top-up PayMongo session created', [
                'user_id'    => $user->id,
                'amount'     => $validated['amount'],
                'session_id' => $session['session_id'],
            ]);

            return Inertia::location($session['checkout_url']);

        } catch (\Exception $e) {
            Log::error('[WalletController] Top-up PayMongo session failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return back()->withErrors([
                'amount' => 'Unable to initiate payment: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * PayMongo redirects here after a successful wallet top-up payment.
     * We credit the wallet and redirect to the wallet page.
     */
    public function topupSuccess(Request $request)
    {
        /** @var \App\Models\User $user */
        $user   = auth()->user();
        $wallet = $user->wallet;

        $callbackToken = (string) $request->query('token');
        $sessionToken = (string) session('wallet_topup_token');
        $amount = (float) session('wallet_topup_amount');
        $walletId = (int) session('wallet_topup_wallet_id');

        // Require one-time token + matching wallet context + non-zero amount.
        if (
            !$wallet ||
            $amount <= 0 ||
            !$callbackToken ||
            !$sessionToken ||
            !hash_equals($sessionToken, $callbackToken) ||
            $walletId !== (int) $wallet->id
        ) {
            return redirect()->route('wallet.index')
                ->withErrors(['error' => 'Invalid top-up session.']);
        }

        $wallet->credit(
            $amount,
            'topup',
            'topup_' . uniqid(),
            'Wallet Top-up via PayMongo'
        );

        Log::info('[WalletController] Wallet top-up completed', [
            'user_id'   => $user->id,
            'wallet_id' => $wallet->id,
            'amount'    => $amount,
        ]);

        // Prevent callback replay.
        session()->forget(['wallet_topup_amount', 'wallet_topup_wallet_id', 'wallet_topup_token']);

        return redirect()->route('wallet.index')
            ->with('success', '₱' . number_format($amount, 2) . ' has been added to your wallet!');
    }

    /**
     * PayMongo redirects here if the user cancels the top-up payment.
     */
    public function topupCancel()
    {
        return redirect()->route('wallet.index')
            ->with('warning', 'Top-up cancelled. No payment was made.');
    }

    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        /** @var \App\Models\User $user */
        $user   = auth()->user();
        $wallet = $user->wallet;

        if (!$wallet || $wallet->balance < $validated['amount']) {
            return back()->withErrors(['amount' => 'Insufficient funds for this withdrawal.']);
        }

        $wallet->debit(
            $validated['amount'],
            'withdrawal',
            'withdraw_' . uniqid(),
            'Instant Wallet Withdrawal'
        );

        return back()->with('success', 'Successfully withdrawn ₱' . number_format($validated['amount'], 2));
    }
}
