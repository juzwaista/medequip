<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\DssDistributorSettings;
use Illuminate\Support\Collection;

class DssAlertService
{
    /**
     * Get all alerts for a distributor
     */
    public function getAlertsForDistributor(int $distributorId): array
    {
        $settings = DssDistributorSettings::where('distributor_id', $distributorId)->first();

        // Use defaults if settings don't exist
        $expiryWarningDays = $settings->expiry_warning_days ?? 60;
        $lowStockThreshold = $settings->low_stock_threshold_days ?? 7;
        $enableAlerts = $settings->enable_auto_alerts ?? true;

        if (!$enableAlerts) {
            return [
                'expiry_alerts' => [],
                'low_stock_alerts' => [],
                'total_alerts' => 0,
            ];
        }

        return [
            'expiry_alerts' => $this->getExpiryAlerts($distributorId, $expiryWarningDays),
            'low_stock_alerts' => $this->getLowStockAlerts($distributorId),
            'total_alerts' => 0, // Will be calculated below
        ];
    }

    /**
     * Get inventory items expiring soon
     */
    protected function getExpiryAlerts(int $distributorId, int $days): Collection
    {
        return Inventory::whereHas('product', function ($query) use ($distributorId) {
            $query->where('distributor_id', $distributorId);
        })
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('quantity', '>', 0)
            ->with('product')
            ->get()
            ->map(function ($inventory) {
                // Whole days until end of expiry day (integer, no decimals in UI)
                $daysUntilExpiry = (int) now()->startOfDay()->diffInDays($inventory->expiry_date->copy()->startOfDay());

                return [
                    'id' => $inventory->id,
                    'product_id' => $inventory->product_id,
                    'product_name' => $inventory->product->name,
                    'batch_number' => $inventory->batch_number,
                    'expiry_date' => $inventory->expiry_date->format('Y-m-d'),
                    'expiry_date_display' => $inventory->expiry_date->format('M j, Y'),
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $inventory->quantity,
                    'severity' => $daysUntilExpiry <= 30 ? 'critical' : 'warning',
                ];
            });
    }

    /**
     * Get products with low stock
     */
    protected function getLowStockAlerts(int $distributorId): Collection
    {
        return Product::where('distributor_id', $distributorId)
            ->whereHas('inventory', function ($query) {
                $query->whereRaw('quantity <= reorder_level')
                    ->where(function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNull('product_variation_id')
                                ->whereRaw('NOT EXISTS (SELECT 1 FROM product_variations WHERE product_variations.product_id = inventory.product_id AND product_variations.is_active = 1)');
                        })->orWhere(function ($sub) {
                            $sub->whereNotNull('product_variation_id')
                                ->whereRaw('EXISTS (SELECT 1 FROM product_variations WHERE product_variations.id = inventory.product_variation_id AND product_variations.is_active = 1)');
                        });
                    });
            })
            ->with([
                'inventory' => function ($query) {
                    $query->whereRaw('quantity <= reorder_level')
                        ->where(function ($q) {
                            $q->where(function ($sub) {
                                $sub->whereNull('product_variation_id')
                                    ->whereRaw('NOT EXISTS (SELECT 1 FROM product_variations WHERE product_variations.product_id = inventory.product_id AND product_variations.is_active = 1)');
                            })->orWhere(function ($sub) {
                                $sub->whereNotNull('product_variation_id')
                                    ->whereRaw('EXISTS (SELECT 1 FROM product_variations WHERE product_variations.id = inventory.product_variation_id AND product_variations.is_active = 1)');
                            });
                        });
                }
            ])
            ->get()
            ->flatMap(function ($product) {
                return $product->inventory->map(function ($inventory) use ($product) {
                    return [
                        'id' => $inventory->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'current_quantity' => $inventory->quantity,
                        'reorder_level' => $inventory->reorder_level,
                        'available_stock' => $inventory->availableStock,
                        'batch_number' => $inventory->batch_number,
                    ];
                });
            });
    }

    /**
     * Get total alert count
     */
    public function getAlertCount(int $distributorId): int
    {
        $alerts = $this->getAlertsForDistributor($distributorId);
        return $alerts['expiry_alerts']->count() + $alerts['low_stock_alerts']->count();
    }
}
