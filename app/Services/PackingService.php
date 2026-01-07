<?php

namespace App\Services;

use App\Models\PackInventory;
use App\Models\PackSize;
use App\Models\Packing;
use App\Models\PackingMaterialUsage;
use App\Models\Product;
use App\Models\Unpacking;
use App\Services\PackInventoryService;
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
            $lineSnapshots = $this->hydrateLineSnapshots($cleanLines, $packSizes);

            $totalBulkQty = $this->calculateTotalBulkFromSnapshots($lineSnapshots);
            if ($totalBulkQty <= 0) {
                throw new RuntimeException('Add at least one packing line.');
            }

            $availableBulk = $inventoryService->getOnHand($productId);
            if ($totalBulkQty > $availableBulk) {
                throw new RuntimeException('Insufficient bulk stock. Required '.$totalBulkQty.' exceeds available '.$availableBulk.'.');
            }

            [$materialRequirements, $usageRows] = $this->calculateMaterialRequirements($lineSnapshots, $packSizes);

            $materialProducts = collect();
            if (! empty($materialRequirements)) {
                $materialProducts = Product::whereIn('id', array_keys($materialRequirements))->get()->keyBy('id');

                foreach ($materialRequirements as $materialId => $requiredQty) {
                    $product = $materialProducts[$materialId] ?? null;
                    $available = $inventoryService->getOnHand($materialId);
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

            $packingItems = $packing->items()->createMany(
                $lineSnapshots->map(function ($line) {
                    return [
                        'pack_size_id' => $line['pack_size_id'],
                        'pack_count' => $line['pack_count'],
                        'pack_qty_snapshot' => $line['pack_qty_snapshot'],
                        'pack_uom' => $line['pack_uom'],
                    ];
                })->all()
            );

            foreach ($lineSnapshots as $line) {
                /** @var PackInventory $inventory */
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $productId,
                    'pack_size_id' => $line['pack_size_id'],
                ]);

                $inventory->pack_count = (int) $inventory->pack_count + $line['pack_count'];
                $inventory->save();
            }

            $this->persistMaterialUsages($packing, $usageRows, $packingItems);

            foreach ($materialRequirements as $materialId => $requiredQty) {
                $product = $materialProducts[$materialId] ?? null;
                // Material consumption is captured via packing_material_usages; ledger entry removed.
            }

            return $packing->load('items.packSize', 'product');
        });
    }

    public function unpack(array $payload, array $lines, InventoryService $inventoryService, PackInventoryService $packInventoryService): Unpacking
    {
        $cleanLines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService, $packInventoryService) {
            $productId = (int) $payload['product_id'];
            $packSizes = $this->getPackSizes($productId, $cleanLines->pluck('pack_size_id')->all());

            foreach ($cleanLines as $line) {
                $availablePacks = $packInventoryService->getPackOnHand($productId, $line['pack_size_id']);
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

            return $unpacking->load('items.packSize', 'product');
        });
    }

    public function updatePacking(Packing $packing, array $payload, array $lines, InventoryService $inventoryService): Packing
    {
        if ($packing->is_locked) {
            throw new RuntimeException('Packing is locked and cannot be edited.');
        }

        $cleanLines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($packing, $payload, $cleanLines, $inventoryService) {
            $productId = (int) $payload['product_id'];
            $packSizes = $this->getPackSizes($productId, $cleanLines->pluck('pack_size_id')->all());
            $lineSnapshots = $this->hydrateLineSnapshots($cleanLines, $packSizes);

            $totalBulkQty = $this->calculateTotalBulkFromSnapshots($lineSnapshots);
            if ($totalBulkQty <= 0) {
                throw new RuntimeException('Add at least one packing line.');
            }

            $availableBulk = $inventoryService->getOnHand($productId);
            if ($productId === (int) $packing->product_id) {
                $availableBulk += (float) $packing->total_bulk_qty;
            }

            if ($totalBulkQty > $availableBulk) {
                throw new RuntimeException('Insufficient bulk stock. Required '.$totalBulkQty.' exceeds available '.$availableBulk.'.');
            }

            [$materialRequirements, $usageRows] = $this->calculateMaterialRequirements($lineSnapshots, $packSizes);

            $existingUsage = $packing->materialUsages()
                ->selectRaw('material_product_id, SUM(qty_used) as qty_used')
                ->groupBy('material_product_id')
                ->pluck('qty_used', 'material_product_id');

            $materialProducts = collect();
            if (! empty($materialRequirements)) {
                $materialProducts = Product::whereIn('id', array_keys($materialRequirements))->get()->keyBy('id');

                foreach ($materialRequirements as $materialId => $requiredQty) {
                    $product = $materialProducts[$materialId] ?? null;
                    $available = $inventoryService->getOnHand($materialId) + (float) ($existingUsage[$materialId] ?? 0);
                    if ($requiredQty > $available) {
                        $name = $product?->name ?: ('Product ID '.$materialId);
                        throw new RuntimeException("Insufficient packing material {$name}. Need {$requiredQty}, available {$available}.");
                    }
                }
            }

            $oldItems = $packing->items()->get();
            $oldProductId = (int) $packing->product_id;

            $packing->update([
                'date' => $payload['date'],
                'product_id' => $productId,
                'total_bulk_qty' => $totalBulkQty,
                'remarks' => $payload['remarks'] ?? null,
            ]);

            foreach ($oldItems as $item) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $oldProductId,
                    'pack_size_id' => $item->pack_size_id,
                ]);

                $inventory->pack_count = max(0, (int) $inventory->pack_count - (int) $item->pack_count);
                $inventory->save();
            }

            $packing->items()->delete();
            $packingItems = $packing->items()->createMany(
                $lineSnapshots->map(function ($line) {
                    return [
                        'pack_size_id' => $line['pack_size_id'],
                        'pack_count' => $line['pack_count'],
                        'pack_qty_snapshot' => $line['pack_qty_snapshot'],
                        'pack_uom' => $line['pack_uom'],
                    ];
                })->all()
            );

            foreach ($lineSnapshots as $line) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $productId,
                    'pack_size_id' => $line['pack_size_id'],
                ]);

                $inventory->pack_count = (int) $inventory->pack_count + $line['pack_count'];
                $inventory->save();
            }

            $this->persistMaterialUsages($packing, $usageRows, $packingItems);

            return $packing->load('items.packSize', 'product');
        });
    }

    public function updateUnpacking(Unpacking $unpacking, array $payload, array $lines, PackInventoryService $packInventoryService): Unpacking
    {
        if ($unpacking->is_locked) {
            throw new RuntimeException('Unpacking is locked and cannot be edited.');
        }

        $cleanLines = $this->normalizeLines($lines);

        return DB::transaction(function () use ($unpacking, $payload, $cleanLines, $packInventoryService) {
            $productId = (int) $payload['product_id'];
            $packSizes = $this->getPackSizes($productId, $cleanLines->pluck('pack_size_id')->all());
            $lineSnapshots = $this->hydrateLineSnapshots($cleanLines, $packSizes);

            $totalBulkQty = $this->calculateTotalBulkFromSnapshots($lineSnapshots);
            if ($totalBulkQty <= 0) {
                throw new RuntimeException('Add at least one unpacking line.');
            }

            $oldItems = $unpacking->items()->get();
            $oldProductId = (int) $unpacking->product_id;
            $oldCounts = $oldItems->groupBy('pack_size_id')->map(fn ($items) => $items->sum('pack_count'));

            foreach ($lineSnapshots as $line) {
                $available = $packInventoryService->getPackOnHand($productId, $line['pack_size_id']);
                if ($productId === $oldProductId) {
                    $available += (int) ($oldCounts[$line['pack_size_id']] ?? 0);
                }

                if ($line['pack_count'] > $available) {
                    throw new RuntimeException('Insufficient packs for the selected size. Available '.$available.', requested '.$line['pack_count'].'.');
                }
            }

            $unpacking->update([
                'date' => $payload['date'],
                'product_id' => $productId,
                'total_bulk_qty' => $totalBulkQty,
                'remarks' => $payload['remarks'] ?? null,
            ]);

            foreach ($oldItems as $item) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $oldProductId,
                    'pack_size_id' => $item->pack_size_id,
                ]);

                $inventory->pack_count = (int) $inventory->pack_count + (int) $item->pack_count;
                $inventory->save();
            }

            $unpacking->items()->delete();
            $unpacking->items()->createMany(
                $lineSnapshots->map(function ($line) {
                    return [
                        'pack_size_id' => $line['pack_size_id'],
                        'pack_count' => $line['pack_count'],
                        'pack_qty_snapshot' => $line['pack_qty_snapshot'],
                        'pack_uom' => $line['pack_uom'],
                    ];
                })->all()
            );

            foreach ($lineSnapshots as $line) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $productId,
                    'pack_size_id' => $line['pack_size_id'],
                ]);

                $inventory->pack_count = max(0, (int) $inventory->pack_count - $line['pack_count']);
                $inventory->save();
            }

            return $unpacking->load('items.packSize', 'product');
        });
    }

    public function lock(Packing $packing, int $userId): Packing
    {
        if ($packing->is_locked) {
            return $packing;
        }

        $packing->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $packing->refresh();
    }

    public function unlock(Packing $packing): Packing
    {
        $packing->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $packing->refresh();
    }

    public function lockUnpacking(Unpacking $unpacking, int $userId): Unpacking
    {
        if ($unpacking->is_locked) {
            return $unpacking;
        }

        $unpacking->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $unpacking->refresh();
    }

    public function unlockUnpacking(Unpacking $unpacking): Unpacking
    {
        $unpacking->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $unpacking->refresh();
    }

    public function deletePacking(Packing $packing): void
    {
        if ($packing->is_locked) {
            throw new RuntimeException('Packing is locked and cannot be deleted.');
        }

        DB::transaction(function () use ($packing) {
            $oldItems = $packing->items()->get();

            foreach ($oldItems as $item) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $packing->product_id,
                    'pack_size_id' => $item->pack_size_id,
                ]);

                $inventory->pack_count = max(0, (int) $inventory->pack_count - (int) $item->pack_count);
                $inventory->save();
            }

            $packing->items()->delete();
            $packing->materialUsages()->delete();
            $packing->delete();
        });
    }

    public function deleteUnpacking(Unpacking $unpacking): void
    {
        if ($unpacking->is_locked) {
            throw new RuntimeException('Unpacking is locked and cannot be deleted.');
        }

        DB::transaction(function () use ($unpacking) {
            $oldItems = $unpacking->items()->get();

            foreach ($oldItems as $item) {
                $inventory = PackInventory::firstOrNew([
                    'product_id' => $unpacking->product_id,
                    'pack_size_id' => $item->pack_size_id,
                ]);

                $inventory->pack_count = (int) $inventory->pack_count + (int) $item->pack_count;
                $inventory->save();
            }

            $unpacking->items()->delete();
            $unpacking->delete();
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
    private function hydrateLineSnapshots(Collection $lines, Collection $packSizes): Collection
    {
        return $lines->map(function ($line) use ($packSizes) {
            $packSize = $packSizes[$line['pack_size_id']];

            return [
                'pack_size_id' => $line['pack_size_id'],
                'pack_count' => $line['pack_count'],
                'pack_qty_snapshot' => (float) $packSize->pack_qty,
                'pack_uom' => $packSize->pack_uom,
            ];
        });
    }

    /**
     * @param  Collection<int, array{pack_size_id:int, pack_count:int, pack_qty_snapshot:float|int}>  $lines
     */
    private function calculateTotalBulkFromSnapshots(Collection $lines): float
    {
        return $lines->sum(function ($line) {
            return (float) $line['pack_qty_snapshot'] * (int) $line['pack_count'];
        });
    }

    /**
     * @param  Collection<int, array{pack_size_id:int, pack_count:int, pack_qty_snapshot:float|int}>  $lines
     * @param  Collection<int, PackSize>  $packSizes
     * @return array{0: array<int,float>, 1: array<int,array{line_index:int, pack_size_id:int, material_product_id:int, qty_used:float, uom:?string}>}
     */
    private function calculateMaterialRequirements(Collection $lines, Collection $packSizes): array
    {
        $materialRequirements = [];
        $usageRows = [];

        foreach ($lines as $index => $line) {
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
                $usageRows[] = [
                    'line_index' => $index,
                    'pack_size_id' => $line['pack_size_id'],
                    'material_product_id' => $material->material_product_id,
                    'qty_used' => $required,
                    'uom' => $material->uom ?? $product->uom,
                ];
            }
        }

        return [$materialRequirements, $usageRows];
    }

    /**
     * @param  array<int, array{line_index:int, pack_size_id:int, material_product_id:int, qty_used:float, uom:?string}>  $usageRows
     * @param  iterable<int, \App\Models\PackingItem>  $packingItems
     */
    private function persistMaterialUsages(Packing $packing, array $usageRows, iterable $packingItems): void
    {
        $packing->materialUsages()->delete();

        if (empty($usageRows)) {
            return;
        }

        $items = collect($packingItems)->values();
        $now = now();

        $rows = collect($usageRows)->map(function ($row) use ($items, $packing, $now) {
            $packingItem = $items->get($row['line_index']);

            return [
                'packing_id' => $packing->id,
                'packing_item_id' => $packingItem?->id,
                'pack_size_id' => $row['pack_size_id'],
                'material_product_id' => $row['material_product_id'],
                'qty_used' => $row['qty_used'],
                'uom' => $row['uom'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        PackingMaterialUsage::insert($rows);
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
