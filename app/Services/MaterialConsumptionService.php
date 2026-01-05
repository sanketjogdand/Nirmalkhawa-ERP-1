<?php

namespace App\Services;

use App\Models\MaterialConsumption;
use App\Models\MaterialConsumptionLine;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MaterialConsumptionService
{
    public function create(array $payload, array $lines, InventoryService $inventoryService): MaterialConsumption
    {
        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one line.');
        }

        $this->ensureStockAvailable($cleanLines, $inventoryService, $payload['consumption_date'] ?? null);

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService) {
            $record = MaterialConsumption::create([
                'consumption_date' => $payload['consumption_date'],
                'consumption_type' => $payload['consumption_type'],
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? Auth::id(),
            ]);

            $lines = $this->persistLines($record, $cleanLines);

            return $record->load(['lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function update(MaterialConsumption $consumption, array $payload, array $lines, InventoryService $inventoryService): MaterialConsumption
    {
        if ($consumption->is_locked) {
            throw new RuntimeException('Locked records cannot be edited.');
        }

        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one line.');
        }

        $existingQty = $this->lineQtyByProduct($consumption);
        $this->ensureStockAvailable(
            $cleanLines,
            $inventoryService,
            $payload['consumption_date'] ?? $consumption->consumption_date,
            $existingQty
        );

        return DB::transaction(function () use ($consumption, $payload, $cleanLines, $inventoryService) {
            $consumption->update([
                'consumption_date' => $payload['consumption_date'],
                'consumption_type' => $payload['consumption_type'],
                'remarks' => $payload['remarks'] ?? null,
            ]);

            $consumption->lines()->delete();
            $lines = $this->persistLines($consumption, $cleanLines);

            return $consumption->load(['lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function delete(MaterialConsumption $consumption, InventoryService $inventoryService): void
    {
        if ($consumption->is_locked) {
            throw new RuntimeException('Record is locked. Unlock before deleting.');
        }

        DB::transaction(function () use ($consumption, $inventoryService) {
            $consumption->lines()->delete();
            $consumption->delete();
        });
    }

    public function lock(MaterialConsumption $consumption, int $userId): MaterialConsumption
    {
        if ($consumption->is_locked) {
            return $consumption;
        }

        $consumption->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $consumption->refresh();
    }

    public function unlock(MaterialConsumption $consumption): MaterialConsumption
    {
        $consumption->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $consumption->refresh();
    }

    private function normalizeLines(array $lines): Collection
    {
        $productIds = collect($lines)->pluck('product_id')->filter()->unique()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($lines)
            ->map(function ($line) use ($products) {
                $productId = (int) ($line['product_id'] ?? 0);
                $product = $products[$productId] ?? null;

                if (! $product || ! $product->can_consume || ! $product->can_stock) {
                    throw new RuntimeException('Invalid product selected for consumption.');
                }

                $qty = isset($line['qty']) ? (float) $line['qty'] : 0;
                if ($qty <= 0) {
                    throw new RuntimeException('Quantity must be greater than zero.');
                }

                return [
                    'product_id' => $productId,
                    'product' => $product,
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
        $consumptionDate = null,
        array $existingQty = []
    ): void {
        $totals = $lines->groupBy('product_id')->map(function ($items) {
            return $items->sum(fn ($item) => (float) $item['qty']);
        });

        $date = $consumptionDate ? Carbon::parse($consumptionDate) : null;

        foreach ($totals as $productId => $requiredQty) {
            $baseAvailable = $date
                ? $inventoryService->getStockAsOf((int) $productId, $date)
                : $inventoryService->getOnHand((int) $productId);

            $available = $baseAvailable + ($existingQty[(int) $productId] ?? 0);
            if ($requiredQty > $available) {
                $productName = $lines->firstWhere('product_id', (int) $productId)['product']->name ?? ('Product ID '.$productId);
                $shortage = round($requiredQty - $available, 3);
                throw new RuntimeException("Insufficient stock for {$productName}. Available {$available}, required {$requiredQty}. Short by {$shortage}.");
            }
        }
    }

    private function persistLines(MaterialConsumption $consumption, Collection $lines): Collection
    {
        $created = collect();

        foreach ($lines as $line) {
            $model = MaterialConsumptionLine::create([
                'material_consumption_id' => $consumption->id,
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'uom' => $line['uom'],
                'remarks' => $line['remarks'] ?? null,
            ]);
            $model->setRelation('product', $line['product']);
            $created->push($model);
        }

        return $created;
    }

    private function lineQtyByProduct(MaterialConsumption $consumption): array
    {
        return $consumption->lines()
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id')
            ->map(fn ($qty) => (float) $qty)
            ->toArray();
    }
}
