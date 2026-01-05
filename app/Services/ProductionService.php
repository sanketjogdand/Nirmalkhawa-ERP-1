<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionInput;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionService
{
    public function create(array $payload, array $inputs, InventoryService $inventoryService): ProductionBatch
    {
        return $this->saveInternal(new ProductionBatch(), $payload, $inputs, $inventoryService);
    }

    public function update(ProductionBatch $batch, array $payload, array $inputs, InventoryService $inventoryService): ProductionBatch
    {
        if ($batch->is_locked) {
            throw new RuntimeException('Locked batches cannot be edited.');
        }

        return $this->saveInternal($batch, $payload, $inputs, $inventoryService);
    }

    public function delete(ProductionBatch $batch, InventoryService $inventoryService): void
    {
        if ($batch->is_locked) {
            throw new RuntimeException('Locked batches cannot be deleted.');
        }

        DB::transaction(function () use ($batch, $inventoryService) {
            $inventoryService->reverseReference(ProductionBatch::class, $batch->id, 'Production batch deleted - reversal');
            $batch->inputs()->delete();
            $batch->delete();
        });
    }

    public function lock(ProductionBatch $batch, int $userId): ProductionBatch
    {
        if ($batch->is_locked) {
            return $batch;
        }

        $batch->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $batch->refresh();
    }

    public function unlock(ProductionBatch $batch): ProductionBatch
    {
        $batch->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $batch->refresh();
    }

    private function saveInternal(
        ProductionBatch $batch,
        array $payload,
        array $inputs,
        InventoryService $inventoryService
    ): ProductionBatch {
        $cleanInputs = $this->normalizeInputs($inputs);
        $yieldData = $this->computeYield((float) $payload['actual_output_qty'], $cleanInputs);

        return DB::transaction(function () use ($batch, $payload, $cleanInputs, $yieldData, $inventoryService) {
            $isNew = ! $batch->exists;
            $existingUsage = $isNew ? [] : $this->inputTotalsByProduct($batch);

            $this->ensureStockAvailable(
                $cleanInputs,
                $inventoryService,
                $payload['date'] ?? $batch->date,
                $existingUsage
            );

            if (! $isNew) {
                $batch->inputs()->delete();
            }

            $data = array_merge($payload, $yieldData, [
                'created_by' => $batch->created_by ?? Auth::id(),
            ]);

            if ($isNew) {
                $batch = ProductionBatch::create($data);
            } else {
                $batch->update($data);
            }

            $batch->inputs()->createMany($cleanInputs);

            return $batch->fresh(['inputs.materialProduct', 'outputProduct', 'recipe', 'yieldBaseProduct']);
        });
    }

    private function normalizeInputs(array $inputs): array
    {
        return collect($inputs)
            ->map(function ($item) {
                return [
                    'recipe_item_id' => $item['recipe_item_id'] ?? null,
                    'material_product_id' => (int) $item['material_product_id'],
                    'planned_qty' => (float) ($item['planned_qty'] ?? 0),
                    'actual_qty_used' => isset($item['actual_qty_used']) ? (float) $item['actual_qty_used'] : null,
                    'uom' => $item['uom'] ?? '',
                    'is_yield_base' => (bool) ($item['is_yield_base'] ?? false),
                ];
            })
            ->filter(fn ($item) => $item['material_product_id'] && $item['actual_qty_used'] !== null)
            ->values()
            ->all();
    }

    private function computeYield(float $actualOutputQty, array $inputs): array
    {
        $yieldInput = collect($inputs)->firstWhere('is_yield_base', true);

        if (! $yieldInput || empty($yieldInput['actual_qty_used'])) {
            return [
                'yield_base_product_id' => null,
                'yield_base_actual_qty_used' => null,
                'yield_ratio' => null,
                'yield_pct' => null,
            ];
        }

        $baseQty = (float) $yieldInput['actual_qty_used'];
        if ($baseQty <= 0) {
            return [
                'yield_base_product_id' => (int) $yieldInput['material_product_id'],
                'yield_base_actual_qty_used' => $baseQty,
                'yield_ratio' => null,
                'yield_pct' => null,
            ];
        }

        $ratio = round($actualOutputQty / $baseQty, 4);

        return [
            'yield_base_product_id' => (int) $yieldInput['material_product_id'],
            'yield_base_actual_qty_used' => $baseQty,
            'yield_ratio' => $ratio,
            'yield_pct' => round($ratio * 100, 2),
        ];
    }

    private function ensureStockAvailable(
        array $inputs,
        InventoryService $inventoryService,
        $productionDate = null,
        array $existingUsage = []
    ): void
    {
        $productIds = collect($inputs)->pluck('material_product_id')->unique()->filter()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($inputs as $input) {
            $product = $products[$input['material_product_id']] ?? null;
            if (! $product) {
                throw new RuntimeException('Invalid material selected.');
            }
            if (! $product->can_consume) {
                throw new RuntimeException('Material '.$product->name.' is not allowed for consumption.');
            }
            if (! $product->can_stock) {
                continue;
            }

            $qty = (float) ($input['actual_qty_used'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $available = $productionDate
                ? $inventoryService->getStockAsOf($input['material_product_id'], $productionDate)
                : $inventoryService->getOnHand($input['material_product_id']);
            $available += $existingUsage[$input['material_product_id']] ?? 0;
            if ($qty > $available) {
                $productName = $products[$input['material_product_id']]->name ?? ('Product ID '.$input['material_product_id']);
                $shortage = round($qty - $available, 3);
                throw new RuntimeException("Insufficient stock for {$productName}. Available {$available}, required {$qty}. Short by {$shortage}.");
            }
        }
    }

    private function inputTotalsByProduct(ProductionBatch $batch): array
    {
        return $batch->inputs()
            ->whereNotNull('actual_qty_used')
            ->where('actual_qty_used', '>', 0)
            ->get()
            ->groupBy('material_product_id')
            ->map(fn ($rows) => $rows->sum(fn ($row) => (float) $row->actual_qty_used))
            ->all();
    }
}
