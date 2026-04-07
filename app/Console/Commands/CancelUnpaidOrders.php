<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically cancel online orders that are unpaid for more than 24 hours.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to cancel unpaid orders...');

        $threshold = now()->subHours(24);

        $orders = \App\Models\Order::where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->where('payment_method', '!=', 'cod')
            ->whereDoesntHave('invoice.payments', function ($q) {
                $q->where('status', 'verified');
            })
            ->with(['items.inventory'])
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No unpaid orders to cancel.');
            return;
        }

        $count = 0;
        foreach ($orders as $order) {
            try {
                \DB::transaction(function () use ($order) {
                    // Release stock reservations
                    foreach ($order->items as $item) {
                        if ($item->inventory) {
                            $item->inventory->releaseReservation($item->quantity);
                        }
                    }

                    $order->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'notes' => ($order->notes ? $order->notes . "\n" : "") . "[System] Automatically cancelled due to non-payment after 24 hours.",
                    ]);
                });

                $count++;
                $this->line("Cancelled order: {$order->order_number}");
            } catch (\Exception $e) {
                $this->error("Failed to cancel order {$order->order_number}: " . $e->getMessage());
                \Log::error("[CancelUnpaidOrders] Failed: " . $e->getMessage(), ['order_id' => $order->id]);
            }
        }

        $this->info("Successfully cancelled {$count} unpaid orders.");
    }
}
