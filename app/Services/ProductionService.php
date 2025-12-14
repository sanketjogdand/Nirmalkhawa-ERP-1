<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\ProductionInput;
use App\Models\StockLedger;
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

            if (! $isNew) {
                $inventoryService->reverseReference(ProductionBatch::class, $batch->id, 'Production batch updated - reversal');
                \App\Models\StockLedger::where('reference_type', ProductionBatch::class)
                    ->where('reference_id', $batch->id)
                    ->delete();
            }

            $data = array_merge($payload, $yieldData, [
                'created_by' => $batch->created_by ?? Auth::id(),
            ]);

            if ($isNew) {
                $batch = ProductionBatch::create($data);
            } else {
                $batch->update($data);
            }

            $batch->inputs()->delete();
            $batch->inputs()->createMany($cleanInputs);

            $this->ensureStockAvailable($cleanInputs, $inventoryService);
            $this->postLedger($batch, $inventoryService);

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

    private function ensureStockAvailable(array $inputs, InventoryService $inventoryService): void
    {
        $productIds = collect($inputs)->pluck('material_product_id')->unique()->filter()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($inputs as $input) {
            $qty = (float) ($input['actual_qty_used'] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $available = $inventoryService->getCurrentStock($input['material_product_id']);
            if ($qty > $available) {
                $productName = $products[$input['material_product_id']]->name ?? ('Product ID '.$input['material_product_id']);
                $shortage = round($qty - $available, 3);
                throw new RuntimeException("Insufficient stock for {$productName}. Available {$available}, required {$qty}. Short by {$shortage}.");
            }
        }
    }

    private function postLedger(ProductionBatch $batch, InventoryService $inventoryService): void
    {
        $timestamp = $batch->date ? $batch->date->toDateString().' 12:00:00' : now();
        $reference = [
            'reference_type' => ProductionBatch::class,
            'reference_id' => $batch->id,
            'txn_datetime' => $timestamp,
        ];

        $batch->loadMissing('inputs.materialProduct', 'outputProduct');

        /** @var Collection<int, ProductionInput> $inputs */
        $inputs = $batch->inputs;

        foreach ($inputs as $input) {
            if ($input->actual_qty_used === null || $input->actual_qty_used <= 0) {
                continue;
            }

            $inventoryService->postOut(
                $input->material_product_id,
                (float) $input->actual_qty_used,
                StockLedger::TYPE_PRODUCTION_OUT,
                array_merge($reference, [
                    'uom' => $input->uom ?: ($input->materialProduct->uom ?? null),
                    'remarks' => 'Production input for batch '.$batch->id,
                ])
            );
        }

        $inventoryService->postIn(
            $batch->output_product_id,
            (float) $batch->actual_output_qty,
            StockLedger::TYPE_PRODUCTION_IN,
            array_merge($reference, [
                'uom' => $batch->output_uom ?: ($batch->outputProduct->uom ?? null),
                'remarks' => 'Production output for batch '.$batch->id,
            ])
        );
    }
}
