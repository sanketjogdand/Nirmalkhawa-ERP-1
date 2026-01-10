<?php

namespace App\Services;

use App\Models\Dispatch;
use App\Models\DispatchLine;
use App\Models\PackSize;
use App\Models\Product;
use App\Services\PackInventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DispatchService
{
    public function create(array $payload, array $lines, InventoryService $inventoryService, PackInventoryService $packInventoryService): Dispatch
    {
        $cleanLines = $this->prepareLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one dispatch line.');
        }

        return DB::transaction(function () use ($payload, $cleanLines, $inventoryService, $packInventoryService) {
            $dispatch = Dispatch::create([
                'dispatch_no' => $payload['dispatch_no'] ?? $this->generateDispatchNo($payload['dispatch_date'] ?? now()->toDateString()),
                'dispatch_date' => $payload['dispatch_date'],
                'delivery_mode' => $payload['delivery_mode'],
                'vehicle_no' => $payload['vehicle_no'] ?? null,
                'driver_name' => $payload['driver_name'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'status' => $payload['status'] ?? Dispatch::STATUS_DRAFT,
                'created_by' => $payload['created_by'] ?? Auth::id(),
            ]);

            $this->persistLines($dispatch, $cleanLines);

            if ($dispatch->status === Dispatch::STATUS_POSTED) {
                $this->applyPosting($dispatch, $inventoryService, $packInventoryService);
            }

            return $dispatch->load('lines.product', 'lines.customer', 'lines.packSize');
        });
    }

    public function update(Dispatch $dispatch, array $payload, array $lines, InventoryService $inventoryService, PackInventoryService $packInventoryService): Dispatch
    {
        if ($dispatch->is_locked) {
            throw new RuntimeException('Locked dispatch cannot be edited.');
        }

        $cleanLines = $this->prepareLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one dispatch line.');
        }

        return DB::transaction(function () use ($dispatch, $payload, $cleanLines, $inventoryService, $packInventoryService) {
            $wasPosted = $dispatch->status === Dispatch::STATUS_POSTED;

            if ($wasPosted) {
                $this->reversePosting($dispatch, $inventoryService, $packInventoryService, 'Dispatch updated - reversal');
            }

            $dispatch->update([
                'dispatch_date' => $payload['dispatch_date'],
                'delivery_mode' => $payload['delivery_mode'],
                'vehicle_no' => $payload['vehicle_no'] ?? null,
                'driver_name' => $payload['driver_name'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'status' => $payload['status'] ?? Dispatch::STATUS_DRAFT,
            ]);

            $dispatch->lines()->delete();
            $this->persistLines($dispatch, $cleanLines);

            if ($dispatch->status === Dispatch::STATUS_POSTED) {
                $this->applyPosting($dispatch, $inventoryService, $packInventoryService);
            }

            return $dispatch->load('lines.product', 'lines.customer', 'lines.packSize');
        });
    }

    public function delete(Dispatch $dispatch, InventoryService $inventoryService, PackInventoryService $packInventoryService): void
    {
        if ($dispatch->is_locked) {
            throw new RuntimeException('Locked dispatch cannot be deleted.');
        }

        DB::transaction(function () use ($dispatch, $inventoryService, $packInventoryService) {
            if ($dispatch->status === Dispatch::STATUS_POSTED) {
                $this->reversePosting($dispatch, $inventoryService, $packInventoryService, 'Dispatch deleted - reversal');
            }

            $dispatch->lines()->delete();
            $dispatch->delete();
        });
    }

    public function post(Dispatch $dispatch, InventoryService $inventoryService, PackInventoryService $packInventoryService): Dispatch
    {
        if ($dispatch->is_locked) {
            throw new RuntimeException('Locked dispatch cannot be posted.');
        }

        if ($dispatch->status === Dispatch::STATUS_POSTED) {
            return $dispatch;
        }

        return DB::transaction(function () use ($dispatch, $inventoryService, $packInventoryService) {
            $this->applyPosting($dispatch, $inventoryService, $packInventoryService);

            return $dispatch->refresh()->load('lines.product', 'lines.customer', 'lines.packSize');
        });
    }

    public function lock(Dispatch $dispatch, int $userId): Dispatch
    {
        if ($dispatch->is_locked) {
            return $dispatch;
        }

        $dispatch->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $dispatch->refresh();
    }

    public function unlock(Dispatch $dispatch): Dispatch
    {
        $dispatch->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $dispatch->refresh();
    }

    private function prepareLines(array $lines): Collection
    {
        $normalized = collect($lines)
            ->map(function ($line) {
                $mode = strtoupper($line['sale_mode'] ?? DispatchLine::MODE_BULK);

                return [
                    'customer_id' => isset($line['customer_id']) ? (int) $line['customer_id'] : null,
                    'invoice_id' => isset($line['invoice_id']) && $line['invoice_id'] !== '' ? (int) $line['invoice_id'] : null,
                    'product_id' => isset($line['product_id']) ? (int) $line['product_id'] : null,
                    'sale_mode' => $mode === DispatchLine::MODE_PACK ? DispatchLine::MODE_PACK : DispatchLine::MODE_BULK,
                    'qty_bulk' => isset($line['qty_bulk']) ? (float) $line['qty_bulk'] : null,
                    'uom' => $line['uom'] ?? null,
                    'pack_size_id' => isset($line['pack_size_id']) ? (int) $line['pack_size_id'] : null,
                    'pack_count' => isset($line['pack_count']) ? (int) $line['pack_count'] : 0,
                ];
            })
            ->filter(function ($line) {
                if (! $line['customer_id'] || ! $line['product_id']) {
                    return false;
                }

                if ($line['sale_mode'] === DispatchLine::MODE_BULK) {
                    return $line['qty_bulk'] !== null && $line['qty_bulk'] > 0;
                }

                return $line['pack_size_id'] !== null && $line['pack_count'] > 0;
            })
            ->values();

        $packSizeIds = $normalized
            ->where('sale_mode', DispatchLine::MODE_PACK)
            ->pluck('pack_size_id')
            ->filter()
            ->unique()
            ->values();

        $packSizes = PackSize::whereIn('id', $packSizeIds)->get()->keyBy('id');
        $productIds = $normalized->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return $normalized->map(function ($line) use ($packSizes, $products) {
            $product = $products[$line['product_id']] ?? null;
            if (! $product) {
                throw new RuntimeException('Invalid product selected.');
            }
            if (! $product->can_sell) {
                throw new RuntimeException('Product '.$product->name.' cannot be dispatched.');
            }

            if ($line['sale_mode'] === DispatchLine::MODE_PACK) {
                $packSize = $packSizes[$line['pack_size_id']] ?? null;
                if (! $packSize || $packSize->product_id !== $product->id) {
                    throw new RuntimeException('Selected pack size does not belong to the product.');
                }

                $line['pack_qty_snapshot'] = (float) $packSize->pack_qty;
                $line['pack_uom'] = $packSize->pack_uom;
                $line['computed_total_qty'] = round((float) $packSize->pack_qty * (int) $line['pack_count'], 3);
                $line['qty_bulk'] = null;
                $line['uom'] = $line['uom'] ?? $packSize->pack_uom;
            } else {
                $line['pack_size_id'] = null;
                $line['pack_count'] = 0;
                $line['pack_qty_snapshot'] = null;
                $line['pack_uom'] = null;
                $line['computed_total_qty'] = $line['qty_bulk'] ?? 0;
                $line['uom'] = $line['uom'] ?? $product->uom;
            }

            return $line;
        });
    }

    private function persistLines(Dispatch $dispatch, Collection $lines): void
    {
        $dispatch->lines()->createMany(
            $lines->map(function ($line) {
                return $line;
            })->all()
        );
    }

    private function applyPosting(Dispatch $dispatch, InventoryService $inventoryService, PackInventoryService $packInventoryService): void
    {
        $dispatch->loadMissing('lines.product');
        $this->ensureStockAvailable($dispatch, $inventoryService, $packInventoryService);

        $dispatch->update(['status' => Dispatch::STATUS_POSTED]);
    }

    private function reversePosting(Dispatch $dispatch, InventoryService $inventoryService, PackInventoryService $packInventoryService, string $remarks = 'Dispatch reversal'): void
    {
        $dispatch->loadMissing('lines');
    }

    private function ensureStockAvailable(Dispatch $dispatch, InventoryService $inventoryService, PackInventoryService $packInventoryService): void
    {
        $dispatch->loadMissing('lines.product');
        $dispatchDate = $dispatch->dispatch_date ? $dispatch->dispatch_date->toDateString() : now()->toDateString();

        foreach ($dispatch->lines as $line) {
            if ($line->sale_mode === DispatchLine::MODE_BULK) {
                $required = (float) ($line->qty_bulk ?? 0);
                $available = $inventoryService->getOnHandAsOf((int) $line->product_id, $dispatchDate);
                if ($dispatch->status === Dispatch::STATUS_POSTED) {
                    $available += $required;
                }

                if ($required > $available) {
                    $name = $line->product->name ?? ('Product ID '.$line->product_id);
                    $shortage = round($required - $available, 3);
                    throw new RuntimeException(
                        "Insufficient stock for {$name}. Available ".number_format($available, 3)
                        .", required ".number_format($required, 3).". Short by ".number_format($shortage, 3).'.'
                    );
                }
            } else {
                $required = (int) ($line->pack_count ?? 0);
                $available = $packInventoryService->getPackOnHandAsOf(
                    (int) $line->product_id,
                    (int) $line->pack_size_id,
                    $dispatchDate
                );
                if ($dispatch->status === Dispatch::STATUS_POSTED) {
                    $available += $required;
                }

                if ($required > $available) {
                    $name = $line->product->name ?? ('Product ID '.$line->product_id);
                    $shortage = $required - $available;
                    throw new RuntimeException(
                        "Insufficient packs for {$name}. Available {$available}, required {$required}. Short by {$shortage}."
                    );
                }
            }
        }
    }

    private function generateDispatchNo(string $dispatchDate): string
    {
        $prefix = 'DSP-'.date('Ymd', strtotime($dispatchDate)).'-';

        $last = Dispatch::withTrashed()
            ->where('dispatch_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('dispatch_no');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $next = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
