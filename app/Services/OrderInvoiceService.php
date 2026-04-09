<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class OrderInvoiceService
{
    /**
     * Create invoice + initial payment record for an order (online checkout).
     * Idempotent: returns existing invoice if already present.
     */
    public function createInvoiceAndPayment(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $order->loadMissing('items', 'invoice');

            if ($order->invoice) {
                return $order->invoice;
            }

            $itemSubtotal = (float) $order->subtotal;
            $shipping = (float) $order->shipping_fee;
            $totalAmount = (float) $order->total_amount;

            $isWallet = $order->payment_method === 'wallet';
            $isCod    = $order->payment_method === 'cod';

            $invoice = Invoice::create([
                'order_id'       => $order->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'subtotal'       => $itemSubtotal,
                'shipping_fee'   => $shipping,
                'tax'            => (float) ($order->vat_amount ?? 0),
                'discount'       => (float) ($order->discount_amount ?? 0),
                'total_amount'   => $totalAmount,
                'status'         => $isWallet ? 'paid' : 'unpaid',
                'due_date'       => now()->addDays(7),
            ]);

            $fees = Payment::calculateFees($totalAmount);

            if ($isCod) {
                // COD: invoice exists for record-keeping; payment is collected by courier on delivery.
                // No escrow, no platform fee yet — marked verified when distributor confirms remittance.
                $payment = Payment::create([
                    'invoice_id'           => $invoice->id,
                    'payment_method'       => 'cash',
                    'amount'               => $totalAmount,
                    'status'               => 'pending',
                    'escrow_status'        => 'held',
                    'platform_fee_rate'    => 0,
                    'platform_fee_amount'  => 0,
                    'net_seller_amount'    => $totalAmount, // Full amount goes to seller for COD
                    'verified_at'          => null,
                    'paymongo_status'      => null,
                ]);
            } else {
                $paymentMethod = $isWallet ? 'wallet' : 'paymongo';
                $paymentStatus = $isWallet ? 'verified' : 'pending';
                $paymongoStatus = $isWallet ? null : 'pending';

                $payment = Payment::create([
                    'invoice_id'           => $invoice->id,
                    'payment_method'       => $paymentMethod,
                    'amount'               => $totalAmount,
                    'status'               => $paymentStatus,
                    'escrow_status'        => 'held',
                    'platform_fee_rate'    => $fees['platform_fee_rate'],
                    'platform_fee_amount'  => $fees['platform_fee_amount'],
                    'net_seller_amount'    => $fees['net_seller_amount'],
                    'verified_at'          => $isWallet ? now() : null,
                    'paymongo_status'      => $paymongoStatus,
                ]);

                if ($isWallet) {
                    $payment->creditSellerWalletOnVerification();
                }
            }

            return $invoice->fresh(['payments']);
        });
    }
}
