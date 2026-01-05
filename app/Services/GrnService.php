<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnLine;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GrnService
{
    public function create(array $payload, array $lines, InventoryService $inventoryService): Grn
    {
        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one line.');
        }

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService) {
            $grn = Grn::create([
                'supplier_id' => $payload['supplier_id'],
                'purchase_id' => $payload['purchase_id'] ?? null,
                'grn_date' => $payload['grn_date'],
                'remarks' => $payload['remarks'] ?? null,
                'created_by' => $payload['created_by'] ?? Auth::id(),
            ]);

            $lines = $this->persistLines($grn, $cleanLines);

            return $grn->load(['supplier', 'purchase', 'lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function update(Grn $grn, array $payload, array $lines, InventoryService $inventoryService): Grn
    {
        if ($grn->is_locked) {
            throw new RuntimeException('Locked GRN cannot be edited.');
        }

        $cleanLines = $this->normalizeLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one line.');
        }

        return DB::transaction(function () use ($grn, $payload, $cleanLines, $inventoryService) {
            $grn->update([
                'supplier_id' => $payload['supplier_id'],
                'purchase_id' => $payload['purchase_id'] ?? null,
                'grn_date' => $payload['grn_date'],
                'remarks' => $payload['remarks'] ?? null,
            ]);

            $grn->lines()->delete();
            $lines = $this->persistLines($grn, $cleanLines);

            return $grn->load(['supplier', 'purchase', 'lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function delete(Grn $grn, InventoryService $inventoryService): void
    {
        if ($grn->is_locked) {
            throw new RuntimeException('GRN is locked. Ask admin to unlock first.');
        }

        DB::transaction(function () use ($grn, $inventoryService) {
            $grn->lines()->delete();
            $grn->delete();
        });
    }

    public function lock(Grn $grn, int $userId): Grn
    {
        if ($grn->is_locked) {
            return $grn;
        }

        $grn->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $grn->refresh();
    }

    public function unlock(Grn $grn): Grn
    {
        $grn->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $grn->refresh();
    }

    private function normalizeLines(array $lines): Collection
    {
        $productIds = collect($lines)->pluck('product_id')->filter()->unique()->values();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return collect($lines)
            ->map(function ($line) use ($products) {
                $productId = (int) ($line['product_id'] ?? 0);
                $product = $products[$productId] ?? null;
                if (! $product || ! $product->can_purchase || ! $product->can_stock) {
                    throw new RuntimeException('Invalid product selected for GRN.');
                }

                $qty = isset($line['received_qty']) ? (float) $line['received_qty'] : 0;
                if ($qty <= 0) {
                    throw new RuntimeException('Received quantity must be greater than zero.');
                }

                return [
                    'product_id' => $productId,
                    'product' => $product,
                    'received_qty' => $qty,
                    'uom' => $line['uom'] ?? $product->uom,
                    'remarks' => $line['remarks'] ?? null,
                ];
            })
            ->filter()
            ->values();
    }

    private function persistLines(Grn $grn, Collection $lines): Collection
    {
        $created = collect();
        foreach ($lines as $line) {
            $grnLine = GrnLine::create([
                'grn_id' => $grn->id,
                'product_id' => $line['product_id'],
                'received_qty' => $line['received_qty'],
                'uom' => $line['uom'],
                'remarks' => $line['remarks'] ?? null,
            ]);
            $grnLine->setRelation('product', $line['product']);
            $created->push($grnLine);
        }

        return $created;
    }
}
