<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\AutomatedPayoutService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCompleteOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:autocomplete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically complete orders that have been in delivered status for over 3 days';

    /**
     * Execute the console command.
     */
    public function handle(AutomatedPayoutService $payoutService)
    {
        $this->info('Scanning for delivered orders older than 3 days...');

        // Find orders where status is 'delivered' and the updated_at is older than 3 days
        $staleOrders = Order::where('status', 'delivered')
            ->where('updated_at', '<=', Carbon::now()->subDays(3))
            ->get();

        $count = 0;

        foreach ($staleOrders as $order) {
            $order->update(['status' => 'completed']);

            if ($order->escrow_status === 'held') {
                try {
                    $payoutService->releaseEscrow($order);
                    $this->info("Completed and released escrow for order #{$order->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to release escrow for auto-completed order #{$order->id}: " . $e->getMessage());
                    $this->error("Failed to release escrow for order #{$order->id}");
                }
            } else {
                $this->info("Completed order #{$order->id} (no escrow held)");
            }
            $count++;
        }

        $this->info("Auto-completed {$count} orders.");
    }
}
