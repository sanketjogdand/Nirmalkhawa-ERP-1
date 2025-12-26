<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnLine;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockLedger;
use Carbon\Carbon;
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
            $this->postLedger($grn, $lines, $inventoryService);

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
            $this->postReversal($grn, $inventoryService, 'GRN updated - reversal');

            $grn->update([
                'supplier_id' => $payload['supplier_id'],
                'purchase_id' => $payload['purchase_id'] ?? null,
                'grn_date' => $payload['grn_date'],
                'remarks' => $payload['remarks'] ?? null,
            ]);

            $grn->lines()->delete();
            $lines = $this->persistLines($grn, $cleanLines);
            $this->postLedger($grn, $lines, $inventoryService);

            return $grn->load(['supplier', 'purchase', 'lines.product', 'createdBy', 'lockedBy']);
        });
    }

    public function delete(Grn $grn, InventoryService $inventoryService): void
    {
        if ($grn->is_locked) {
            throw new RuntimeException('GRN is locked. Ask admin to unlock first.');
        }

        DB::transaction(function () use ($grn, $inventoryService) {
            $this->postReversal($grn, $inventoryService, 'GRN deleted - reversal');
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

    private function postLedger(Grn $grn, Collection $lines, InventoryService $inventoryService): void
    {
        $timestamp = $this->grnTimestamp($grn);

        foreach ($lines as $line) {
            $inventoryService->postIn(
                (int) $line->product_id,
                (float) $line->received_qty,
                StockLedger::TYPE_GRN_IN,
                [
                    'txn_datetime' => $timestamp,
                    'uom' => $line->uom ?: $line->product?->uom,
                    'remarks' => $line->remarks,
                    'reference_type' => GrnLine::class,
                    'reference_id' => $line->id,
                ]
            );
        }
    }

    private function postReversal(Grn $grn, InventoryService $inventoryService, string $reason): void
    {
        $timestamp = $this->grnTimestamp($grn, true);

        $lines = $grn->lines()->with('product')->get();
        foreach ($lines as $line) {
            $inventoryService->postOut(
                (int) $line->product_id,
                (float) $line->received_qty,
                StockLedger::TYPE_GRN_REVERSAL,
                [
                    'txn_datetime' => $timestamp,
                    'uom' => $line->uom ?: $line->product?->uom,
                    'remarks' => $reason,
                    'reference_type' => GrnLine::class,
                    'reference_id' => $line->id,
                ],
                true
            );
        }
    }

    private function grnTimestamp(Grn $grn, bool $forReversal = false): Carbon
    {
        $baseDate = $grn->grn_date ? $grn->grn_date->toDateString() : now()->toDateString();
        $timePart = now()->format('H:i:s');

        return Carbon::parse("{$baseDate} {$timePart}")
            ->addSeconds($forReversal ? 1 : 0);
    }
}
