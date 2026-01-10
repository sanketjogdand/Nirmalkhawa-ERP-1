<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function getOnHand(int $productId): float
    {
        return (float) DB::table('inventory_ledger_view')
            ->where('product_id', $productId)
            ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as balance')
            ->value('balance');
    }

    public function getOnHandMany(array $productIds): Collection
    {
        $ids = array_filter(array_unique(array_map('intval', $productIds)));
        if (empty($ids)) {
            return collect();
        }

        return DB::table('inventory_ledger_view')
            ->whereIn('product_id', $ids)
            ->selectRaw('product_id, COALESCE(SUM(qty_in - qty_out), 0) as balance')
            ->groupBy('product_id')
            ->pluck('balance', 'product_id');
    }

    public function getStockAsOf(int $productId, $asOf): float
    {
        return $this->getOnHandAsOf($productId, $asOf);
    }

    public function getOnHandAsOf(int $productId, $asOf): float
    {
        $asOfDate = $asOf ? Carbon::parse($asOf)->toDateString() : now()->toDateString();

        return (float) DB::table('inventory_ledger_view')
            ->where('product_id', $productId)
            ->whereDate('txn_date', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(qty_in - qty_out), 0) as balance')
            ->value('balance');
    }

    public function assertSufficientStock(int $productId, float $requiredQty, string $contextMessage = ''): void
    {
        if ($requiredQty <= 0) {
            return;
        }

        $available = $this->getOnHand($productId);
        if ($requiredQty > $available) {
            $product = Product::find($productId);
            $name = $product?->name ?? ('Product ID '.$productId);
            $prefix = $contextMessage ? $contextMessage.': ' : '';
            $shortage = round($requiredQty - $available, 3);
            throw new RuntimeException("{$prefix}Insufficient stock for {$name}. Available {$available}, required {$requiredQty}. Short by {$shortage}.");
        }
    }

    /**
     * Backward-compatible helper for older calls that fetched current stock.
     */
    public function getCurrentStock(int $productId): float
    {
        return $this->getOnHand($productId);
    }

    /**
     * Backward-compatible helper for older calls.
     */
    public function getOnHandStock(int $productId): float
    {
        return $this->getOnHand($productId);
    }

    public function getMilkProduct(string $milkType): Product
    {
        $nameMap = [
            'CM' => 'Raw Milk CM',
            'BM' => 'Raw Milk BM',
            'MIX' => 'Raw Mix Milk',
        ];

        if (! isset($nameMap[$milkType])) {
            throw new RuntimeException('Unsupported milk type '.$milkType);
        }

        $product = Product::where('name', $nameMap[$milkType])->first();

        if (! $product) {
            throw new RuntimeException($nameMap[$milkType].' product is missing. Please run seeds.');
        }

        return $product;
    }
}
