<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Carbon\Carbon;

class InventoryService
{
    public function getCurrentStock(int $productId): float
    {
        return (float) StockLedger::where('product_id', $productId)
            ->whereNotIn('txn_type', [
                StockLedger::TYPE_DISPATCH_PACK_OUT,
                'DISPATCH_PACK', // legacy
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN is_increase = 1 THEN qty ELSE -qty END), 0) as balance')
            ->value('balance');
    }

    public function getStockAsOf(int $productId, $asOf): float
    {
        $asOfTimestamp = $asOf ? Carbon::parse($asOf)->endOfDay() : now();

        return (float) StockLedger::where('product_id', $productId)
            ->where('txn_datetime', '<=', $asOfTimestamp)
            ->whereNotIn('txn_type', [
                StockLedger::TYPE_DISPATCH_PACK_OUT,
                'DISPATCH_PACK', // legacy
            ])
            ->selectRaw('COALESCE(SUM(CASE WHEN is_increase = 1 THEN qty ELSE -qty END), 0) as balance')
            ->value('balance');
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

    public function postIn(int $productId, float $qty, string $txnType = StockLedger::TYPE_IN, array $options = []): StockLedger
    {
        return $this->createEntry($productId, $qty, $txnType, true, $options);
    }

    public function postOut(int $productId, float $qty, string $txnType = StockLedger::TYPE_OUT, array $options = [], bool $allowNegative = false): StockLedger
    {
        return DB::transaction(function () use ($productId, $qty, $txnType, $options, $allowNegative) {
            if (! $allowNegative) {
                $available = $this->getCurrentStock($productId);
                if ($qty > $available) {
                    throw new RuntimeException('Insufficient stock for product ID '.$productId);
                }
            }

            return $this->createEntry($productId, $qty, $txnType, false, $options);
        });
    }

    public function transfer(int $fromProductId, int $toProductId, float $qty, ?string $remarks = null, array $options = []): array
    {
        if ($qty <= 0) {
            throw new RuntimeException('Transfer quantity must be greater than zero.');
        }

        $timestamp = $options['txn_datetime'] ?? now();
        $userId = $options['created_by'] ?? auth()->id();

        return DB::transaction(function () use ($fromProductId, $toProductId, $qty, $remarks, $timestamp, $userId, $options) {
            $transferRemarks = $remarks ?: 'Inventory transfer';

            $outEntry = $this->postOut($fromProductId, $qty, StockLedger::TYPE_TRANSFER, [
                'txn_datetime' => $timestamp,
                'remarks' => $transferRemarks.' - OUT',
                'created_by' => $userId,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
            ], $options['allow_negative'] ?? false);

            $inEntry = $this->postIn($toProductId, $qty, StockLedger::TYPE_TRANSFER, [
                'txn_datetime' => $timestamp,
                'remarks' => $transferRemarks.' - IN',
                'created_by' => $userId,
                'reference_type' => $options['reference_type'] ?? null,
                'reference_id' => $options['reference_id'] ?? null,
            ]);

            return [$outEntry, $inEntry];
        });
    }

    public function reverseReference(string $referenceType, int $referenceId, string $remarks = 'Reversal entry'): void
    {
        $entries = StockLedger::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();

        foreach ($entries as $entry) {
            $this->createEntry(
                $entry->product_id,
                (float) $entry->qty,
                StockLedger::TYPE_ADJ,
                ! $entry->is_increase,
                [
                    'txn_datetime' => now(),
                    'uom' => $entry->uom,
                    'rate' => $entry->rate,
                    'remarks' => $remarks,
                    'created_by' => auth()->id(),
                    'reference_type' => $entry->reference_type,
                    'reference_id' => $entry->reference_id,
                ]
            );
        }
    }

    private function createEntry(int $productId, float $qty, string $txnType, bool $isIncrease, array $options): StockLedger
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }

        $product = Product::findOrFail($productId);

        return StockLedger::create([
            'product_id' => $productId,
            'txn_datetime' => $options['txn_datetime'] ?? now(),
            'txn_type' => $txnType,
            'is_increase' => $isIncrease,
            'qty' => $qty,
            'uom' => $options['uom'] ?? $product->uom,
            'rate' => $options['rate'] ?? null,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'remarks' => $options['remarks'] ?? null,
            'created_by' => $options['created_by'] ?? auth()->id(),
        ]);
    }
}
