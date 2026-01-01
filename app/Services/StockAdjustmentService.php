<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockAdjustmentService
{
    public function create(array $payload, array $lines, InventoryService $inventoryService): StockAdjustment
    {
        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one adjustment line.');
        }

        $this->ensureStockAvailable($cleanLines, $inventoryService, $payload['adjustment_date'] ?? null);

        return DB::transaction(function () use ($payload, $cleanLines) {
            $adjustment = StockAdjustment::create([
                'adjustment_date' => $payload['adjustment_date'],
                'reason' => $payload['reason'],
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? Auth::id(),
            ]);

            $this->persistLines($adjustment, $cleanLines);

            return $adjustment->load(['lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function update(StockAdjustment $adjustment, array $payload, array $lines, InventoryService $inventoryService): StockAdjustment
    {
        if ($adjustment->is_locked) {
            throw new RuntimeException('Locked adjustments cannot be edited.');
        }

        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one adjustment line.');
        }

        $targetDate = $payload['adjustment_date'] ?? $adjustment->adjustment_date;
        $existingTotals = $this->shouldApplyExistingTotals($adjustment, $targetDate)
            ? $this->lineTotals($adjustment)
            : [];
        $this->ensureStockAvailable(
            $cleanLines,
            $inventoryService,
            $targetDate,
            $existingTotals
        );

        return DB::transaction(function () use ($adjustment, $payload, $cleanLines) {
            $adjustment->update([
                'adjustment_date' => $payload['adjustment_date'],
                'reason' => $payload['reason'],
                'remarks' => $payload['remarks'] ?? null,
            ]);

            $adjustment->lines()->delete();
            $this->persistLines($adjustment, $cleanLines);

            return $adjustment->load(['lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function delete(StockAdjustment $adjustment): void
    {
        if ($adjustment->is_locked) {
            throw new RuntimeException('Locked adjustments cannot be deleted.');
        }

        DB::transaction(function () use ($adjustment) {
            $adjustment->lines()->delete();
            $adjustment->delete();
        });
    }

    public function lock(StockAdjustment $adjustment, int $userId): StockAdjustment
    {
        if ($adjustment->is_locked) {
            return $adjustment;
        }

        $adjustment->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $adjustment->refresh();
    }

    public function unlock(StockAdjustment $adjustment): StockAdjustment
    {
        $adjustment->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $adjustment->refresh();
    }

    private function normalizeLines(array $lines): Collection
    {
        $productIds = collect($lines)->pluck('product_id')->filter()->unique()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $seenProducts = [];

        return collect($lines)
            ->map(function ($line) use ($products, &$seenProducts) {
                $productId = (int) ($line['product_id'] ?? 0);
                $product = $products[$productId] ?? null;

                if (! $product || ! $product->can_stock || ! $product->is_active) {
                    throw new RuntimeException('Invalid product selected for adjustment.');
                }

                if (in_array($productId, $seenProducts, true)) {
                    throw new RuntimeException('Duplicate product lines are not allowed.');
                }
                $seenProducts[] = $productId;

                $direction = strtoupper((string) ($line['direction'] ?? ''));
                if (! in_array($direction, [StockAdjustmentLine::DIRECTION_IN, StockAdjustmentLine::DIRECTION_OUT], true)) {
                    throw new RuntimeException('Invalid direction for product '.$product->name.'.');
                }

                $qty = isset($line['qty']) ? (float) $line['qty'] : 0;
                if ($qty <= 0) {
                    throw new RuntimeException('Quantity must be greater than zero.');
                }

                return [
                    'product_id' => $productId,
                    'product' => $product,
                    'direction' => $direction,
                    'qty' => $qty,
                    'uom' => $line['uom'] ?? $product->uom,
                    'remarks' => $line['remarks'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    private function ensureStockAvailable(
        Collection $lines,
        InventoryService $inventoryService,
        $adjustmentDate = null,
        array $existingTotals = []
    ): void {
        $totals = [];
        foreach ($lines as $line) {
            $productId = (int) $line['product_id'];
            $totals[$productId] = $totals[$productId] ?? ['in' => 0.0, 'out' => 0.0];

            if ($line['direction'] === StockAdjustmentLine::DIRECTION_IN) {
                $totals[$productId]['in'] += (float) $line['qty'];
            } else {
                $totals[$productId]['out'] += (float) $line['qty'];
            }
        }

        foreach ($totals as $productId => $byDirection) {
            $baseAvailable = $adjustmentDate
                ? $inventoryService->getStockAsOf((int) $productId, $adjustmentDate)
                : $inventoryService->getCurrentStock((int) $productId);

            $existing = $existingTotals[$productId] ?? ['in' => 0.0, 'out' => 0.0];
            $availableBeforeChange = $baseAvailable - ($existing['in'] ?? 0) + ($existing['out'] ?? 0);
            $effectiveAvailable = $availableBeforeChange + ($byDirection['in'] ?? 0);

            if (($byDirection['out'] ?? 0) > $effectiveAvailable) {
                $productName = $lines->firstWhere('product_id', $productId)['product']->name ?? ('Product ID '.$productId);
                $shortage = round(($byDirection['out'] ?? 0) - $effectiveAvailable, 3);
                $availableText = number_format($effectiveAvailable, 3);
                throw new RuntimeException("Insufficient stock for {$productName}. Available {$availableText}, short by {$shortage}.");
            }
        }
    }

    private function persistLines(StockAdjustment $adjustment, Collection $lines): void
    {
        foreach ($lines as $line) {
            StockAdjustmentLine::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id' => $line['product_id'],
                'direction' => $line['direction'],
                'qty' => $line['qty'],
                'uom' => $line['uom'],
                'remarks' => $line['remarks'],
            ]);
        }
    }

    private function lineTotals(StockAdjustment $adjustment): array
    {
        $totals = [];
        $lines = $adjustment->lines()->get();

        foreach ($lines as $line) {
            $productId = (int) $line->product_id;
            $totals[$productId] = $totals[$productId] ?? ['in' => 0.0, 'out' => 0.0];
            if ($line->direction === StockAdjustmentLine::DIRECTION_IN) {
                $totals[$productId]['in'] += (float) $line->qty;
            } else {
                $totals[$productId]['out'] += (float) $line->qty;
            }
        }

        return $totals;
    }

    private function shouldApplyExistingTotals(StockAdjustment $adjustment, $targetDate): bool
    {
        if (! $adjustment->adjustment_date) {
            return false;
        }

        $currentDate = Carbon::parse($adjustment->adjustment_date);
        $desiredDate = $targetDate ? Carbon::parse($targetDate) : $currentDate;

        return $currentDate->lessThanOrEqualTo($desiredDate);
    }
}
