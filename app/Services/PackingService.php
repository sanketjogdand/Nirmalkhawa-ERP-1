<?php

namespace App\Services;

use App\Models\PackInventory;
use App\Models\PackSize;
use App\Models\PackSizeMaterial;
use App\Models\Packing;
use App\Models\Product;
use App\Models\StockLedger;
use App\Models\Unpacking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PackingService
{
    public function pack(array $payload, array $lines, InventoryService $inventoryService): Packing
    {
        $cleanLines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService) {
            $productId = (int) $payload['product_id'];
            $packSizes = $this->getPackSizes($productId, $cleanLines->pluck('pack_size_id')->all());

            $totalBulkQty = $this->calculateTotalBulk($cleanLines, $packSizes);
            if ($totalBulkQty <= 0) {
                throw new RuntimeException('Add at least one packing line.');
            }

            $availableBulk = $inventoryService->getCurrentStock($productId);
            if ($totalBulkQty > $availableBulk) {
                throw new RuntimeException('Insufficient bulk stock. Required '.$totalBulkQty.' exceeds available '.$availableBulk.'.');
            }

            $materialRequirements = [];
            foreach ($cleanLines as $line) {
                $packSize = $packSizes[$line['pack_size_id']];
                foreach ($packSize->packMaterials as $material) {
                    $required = round((float) $material->qty_per_pack * (int) $line['pack_count'], 3);
                    if ($required <= 0) {
                        continue;
                    }

                    $product = $material->materialProduct;
                    if (! $product || ! $product->is_packing || ! $product->can_consume || ! $product->can_stock) {
                        throw new RuntimeException('Packing material on BOM is invalid. Please fix the pack size BOM.');
                    }

                    $materialRequirements[$material->material_product_id] = ($materialRequirements[$material->material_product_id] ?? 0) + $required;
                }
            }

            $materialProducts = collect();
            if (! empty($materialRequirements)) {
                $materialProducts = Product::whereIn('id', array_keys($materialRequirements))->get()->keyBy('id');

                foreach ($materialRequirements as $materialId => $requiredQty) {
                    $product = $materialProducts[$materialId] ?? null;
                    $available = $inventoryService->getCurrentStock($materialId);
                    if ($requiredQty > $available) {
                        $name = $product?->name ?: ('Product ID '.$materialId);
                        throw new RuntimeException("Insufficient packing material {$name}. Need {$requiredQty}, available {$available}.");
                    }
                }
            }

            $packing = Packing::create([
                'date' => $payload['date'],
                'product_id' => $productId,
                'total_bulk_qty' => $totalBulkQty,
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? auth()->id(),
            ]);

            $packing->items()->createMany(
                $cleanLines->map(function ($line) use ($packSizes) {
                    $packSize = $packSizes[$line['pack_size_id']];

                    return [
                        'pack_size_id' => $line['pack_size_id'],
                        'pack_count' => $line['pack_count'],
                        'pack_qty_snapshot' => $packSize->pack_qty,
                        'pack_uom' => $packSize->pack_uom,
                    ];
                })->all()
            );

            foreach ($cleanLines as $line) {
                /** @var PackInventory $inventory */
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $productId,
                    'pack_size_id' => $line['pack_size_id'],
                ]);

                $inventory->pack_count = (int) $inventory->pack_count + $line['pack_count'];
                $inventory->save();
            }

            $txnTimestamp = $payload['txn_datetime'] ?? (
                ! empty($payload['date'])
                    ? Carbon::parse($payload['date'])->setTimeFromTimeString(now()->format('H:i:s'))
                    : now()
            );

            $inventoryService->postOut(
                $productId,
                $totalBulkQty,
                StockLedger::TYPE_PACKING_BULK_OUT,
                [
                    'txn_datetime' => $txnTimestamp,
                    'reference_type' => Packing::class,
                    'reference_id' => $packing->id,
                    'remarks' => $payload['remarks'] ?? 'Packing',
                    'created_by' => $payload['created_by'] ?? auth()->id(),
                ]
            );

            foreach ($materialRequirements as $materialId => $requiredQty) {
                $product = $materialProducts[$materialId] ?? null;
                $inventoryService->postOut(
                    $materialId,
                    $requiredQty,
                    StockLedger::TYPE_PACKING_MATERIAL_OUT,
                    [
                        'txn_datetime' => $txnTimestamp,
                        'reference_type' => Packing::class,
                        'reference_id' => $packing->id,
                        'remarks' => trim(($payload['remarks'] ?? 'Packing').' (Packing material: '.($product->name ?? 'Material').')'),
                        'created_by' => $payload['created_by'] ?? auth()->id(),
                        'uom' => $product->uom ?? null,
                    ]
                );
            }

            return $packing->load('items.packSize', 'product');
        });
    }

    public function unpack(array $payload, array $lines, InventoryService $inventoryService): Unpacking
    {
        $cleanLines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService) {
            $productId = (int) $payload['product_id'];
            $packSizes = $this->getPackSizes($productId, $cleanLines->pluck('pack_size_id')->all());

            $inventories = PackInventory::where('product_id', $productId)
                ->whereIn('pack_size_id', $cleanLines->pluck('pack_size_id'))
                ->get()
                ->keyBy('pack_size_id');

            foreach ($cleanLines as $line) {
                $inventoryRow = $inventories->get($line['pack_size_id']);
                $availablePacks = $inventoryRow ? (int) $inventoryRow->pack_count : 0;
                if ($line['pack_count'] > $availablePacks) {
                    throw new RuntimeException('Insufficient packs for the selected size. Available '.$availablePacks.', requested '.$line['pack_count'].'.');
                }
            }

            $totalBulkQty = $this->calculateTotalBulk($cleanLines, $packSizes);
            if ($totalBulkQty <= 0) {
                throw new RuntimeException('Add at least one unpacking line.');
            }

            $unpacking = Unpacking::create([
                'date' => $payload['date'],
                'product_id' => $productId,
                'total_bulk_qty' => $totalBulkQty,
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? auth()->id(),
            ]);

            $unpacking->items()->createMany(
                $cleanLines->map(function ($line) use ($packSizes) {
                    $packSize = $packSizes[$line['pack_size_id']];

                    return [
                        'pack_size_id' => $line['pack_size_id'],
                        'pack_count' => $line['pack_count'],
                        'pack_qty_snapshot' => $packSize->pack_qty,
                        'pack_uom' => $packSize->pack_uom,
                    ];
                })->all()
            );

            foreach ($cleanLines as $line) {
                /** @var PackInventory $inventory */
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $productId,
                    'pack_size_id' => $line['pack_size_id'],
                ]);

                $inventory->pack_count = max(0, (int) $inventory->pack_count - $line['pack_count']);
                $inventory->save();
            }

            $txnTimestamp = $payload['txn_datetime'] ?? (
                ! empty($payload['date'])
                    ? Carbon::parse($payload['date'])->setTimeFromTimeString(now()->format('H:i:s'))
                    : now()
            );

            $inventoryService->postIn(
                $productId,
                $totalBulkQty,
                StockLedger::TYPE_UNPACKING_IN,
                [
                    'txn_datetime' => $txnTimestamp,
                    'reference_type' => Unpacking::class,
                    'reference_id' => $unpacking->id,
                    'remarks' => $payload['remarks'] ?? 'Unpacking',
                    'created_by' => $payload['created_by'] ?? auth()->id(),
                ]
            );

            return $unpacking->load('items.packSize', 'product');
        });
    }

    private function normalizeLines(array $lines): Collection
    {
        return collect($lines)
            ->map(function ($line) {
                return [
                    'pack_size_id' => isset($line['pack_size_id']) ? (int) $line['pack_size_id'] : null,
                    'pack_count' => isset($line['pack_count']) ? (int) $line['pack_count'] : 0,
                ];
            })
            ->filter(fn ($line) => $line['pack_size_id'] && $line['pack_count'] > 0)
            ->values();
    }

    /**
     * @param  Collection<int, array{pack_size_id:int, pack_count:int}>  $lines
     * @param  Collection<int, PackSize>  $packSizes
     */
    private function calculateTotalBulk(Collection $lines, Collection $packSizes): float
    {
        return $lines->sum(function ($line) use ($packSizes) {
            $packSize = $packSizes[$line['pack_size_id']] ?? null;
            if (! $packSize) {
                return 0;
            }

            return (float) $packSize->pack_qty * (int) $line['pack_count'];
        });
    }

    /**
     * @param  array<int>  $packSizeIds
     * @return Collection<int, PackSize>
     */
    private function getPackSizes(int $productId, array $packSizeIds): Collection
    {
        $packSizes = PackSize::with(['packMaterials.materialProduct'])
            ->where('product_id', $productId)
            ->whereIn('id', $packSizeIds)
            ->get()
            ->keyBy('id');

        if ($packSizes->count() !== count(array_unique(array_filter($packSizeIds)))) {
            throw new RuntimeException('Invalid pack sizes selected for this product.');
        }

        return $packSizes;
    }
}
