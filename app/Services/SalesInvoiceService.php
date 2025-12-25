<?php

namespace App\Services;

use App\Models\DispatchLine;
use App\Models\PackSize;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SalesInvoiceService
{
    public function create(array $payload, array $lines): SalesInvoice
    {
        $cleanLines = $this->prepareLines($lines);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one invoice line.');
        }

        $totals = $this->calculateTotals($cleanLines);

        return DB::transaction(function () use ($payload, $cleanLines, $totals) {
            $invoice = SalesInvoice::create([
                'customer_id' => $payload['customer_id'],
                'invoice_no' => $payload['invoice_no'] ?? $this->generateInvoiceNo($payload['invoice_date'] ?? now()->toDateString()),
                'invoice_date' => $payload['invoice_date'],
                'status' => $payload['status'] ?? SalesInvoice::STATUS_DRAFT,
                'remarks' => $payload['remarks'] ?? null,
                'subtotal' => $totals['subtotal'],
                'total_gst' => $totals['total_gst'],
                'grand_total' => $totals['grand_total'],
                'created_by' => $payload['created_by'] ?? Auth::id(),
            ]);

            $this->persistLines($invoice, $cleanLines);
            $this->syncDispatchLinks($invoice, $cleanLines);

            return $invoice->load('customer', 'lines.product', 'lines.packSize', 'lines.dispatch', 'lines.dispatchLine');
        });
    }

    public function update(SalesInvoice $invoice, array $payload, array $lines): SalesInvoice
    {
        if ($invoice->is_locked) {
            throw new RuntimeException('Locked invoice cannot be edited.');
        }

        $cleanLines = $this->prepareLines($lines, $invoice->id);
        if ($cleanLines->isEmpty()) {
            throw new RuntimeException('Add at least one invoice line.');
        }

        $totals = $this->calculateTotals($cleanLines);
        $oldDispatchLineIds = $invoice->lines()->whereNotNull('dispatch_line_id')->pluck('dispatch_line_id')->all();

        return DB::transaction(function () use ($invoice, $payload, $cleanLines, $totals, $oldDispatchLineIds) {
            $invoice->lines()->delete();

            $invoice->update([
                'customer_id' => $payload['customer_id'],
                'invoice_date' => $payload['invoice_date'],
                'status' => $payload['status'] ?? $invoice->status,
                'remarks' => $payload['remarks'] ?? null,
                'subtotal' => $totals['subtotal'],
                'total_gst' => $totals['total_gst'],
                'grand_total' => $totals['grand_total'],
            ]);

            $this->persistLines($invoice, $cleanLines);
            $this->syncDispatchLinks($invoice, $cleanLines, $oldDispatchLineIds);

            return $invoice->load('customer', 'lines.product', 'lines.packSize', 'lines.dispatch', 'lines.dispatchLine');
        });
    }

    public function delete(SalesInvoice $invoice): void
    {
        if ($invoice->is_locked) {
            throw new RuntimeException('Locked invoice cannot be deleted.');
        }

        DB::transaction(function () use ($invoice) {
            $dispatchLineIds = $invoice->lines()->whereNotNull('dispatch_line_id')->pluck('dispatch_line_id');
            if ($dispatchLineIds->isNotEmpty()) {
                DispatchLine::whereIn('id', $dispatchLineIds)->update(['invoice_id' => null]);
            }

            $invoice->lines()->delete();
            $invoice->delete();
        });
    }

    public function post(SalesInvoice $invoice): SalesInvoice
    {
        if ($invoice->is_locked) {
            throw new RuntimeException('Locked invoice cannot be posted.');
        }

        if ($invoice->status === SalesInvoice::STATUS_POSTED) {
            return $invoice;
        }

        if ($invoice->lines()->count() === 0) {
            throw new RuntimeException('Cannot post invoice without lines.');
        }

        $totals = $this->recalculateFromStoredLines($invoice);

        $invoice->update([
            'status' => SalesInvoice::STATUS_POSTED,
            'subtotal' => $totals['subtotal'],
            'total_gst' => $totals['total_gst'],
            'grand_total' => $totals['grand_total'],
        ]);

        return $invoice->refresh();
    }

    public function lock(SalesInvoice $invoice, int $userId): SalesInvoice
    {
        if ($invoice->is_locked) {
            return $invoice;
        }

        $invoice->update([
            'is_locked' => true,
            'locked_by' => $userId,
            'locked_at' => now(),
        ]);

        return $invoice->refresh();
    }

    public function unlock(SalesInvoice $invoice): SalesInvoice
    {
        $invoice->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return $invoice->refresh();
    }

    public function generateInvoiceNo(string $invoiceDate): string
    {
        $prefix = 'INV-'.date('Ymd', strtotime($invoiceDate)).'-';

        $last = SalesInvoice::withTrashed()
            ->where('invoice_no', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_no');

        $next = 1;
        if ($last && str_starts_with($last, $prefix)) {
            $next = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function prepareLines(array $lines, ?int $currentInvoiceId = null): Collection
    {
        // Keep as floats so strict checks work with casted float inputs
        $allowedGst = [0.0, 5.0, 12.0, 18.0, 28.0];

        $normalized = collect($lines)->map(function ($line) {
            $mode = strtoupper($line['sale_mode'] ?? SalesInvoiceLine::MODE_BULK);

            return [
                'product_id' => isset($line['product_id']) ? (int) $line['product_id'] : null,
                'sale_mode' => $mode === SalesInvoiceLine::MODE_PACK ? SalesInvoiceLine::MODE_PACK : SalesInvoiceLine::MODE_BULK,
                'rate_per_kg' => isset($line['rate_per_kg']) ? (float) $line['rate_per_kg'] : null,
                'gst_rate_percent' => isset($line['gst_rate_percent']) ? (float) $line['gst_rate_percent'] : null,
                'dispatch_id' => isset($line['dispatch_id']) ? (int) $line['dispatch_id'] : null,
                'dispatch_line_id' => isset($line['dispatch_line_id']) && $line['dispatch_line_id'] !== '' ? (int) $line['dispatch_line_id'] : null,
                'qty_bulk' => isset($line['qty_bulk']) ? (float) $line['qty_bulk'] : null,
                'uom' => $line['uom'] ?? null,
                'pack_size_id' => isset($line['pack_size_id']) ? (int) $line['pack_size_id'] : null,
                'pack_count' => isset($line['pack_count']) ? (int) $line['pack_count'] : 0,
            ];
        })->filter(function ($line) {
            return $line['product_id'] !== null;
        })->values();

        $productIds = $normalized->pluck('product_id')->unique();
        $packSizeIds = $normalized->pluck('pack_size_id')->filter()->unique();
        $dispatchLineIds = $normalized->pluck('dispatch_line_id')->filter()->unique();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $packSizes = $packSizeIds->isNotEmpty() ? PackSize::whereIn('id', $packSizeIds)->get()->keyBy('id') : collect();
        $dispatchLines = $dispatchLineIds->isNotEmpty() ? DispatchLine::whereIn('id', $dispatchLineIds)->get()->keyBy('id') : collect();

        return $normalized->map(function ($line) use ($products, $packSizes, $dispatchLines, $allowedGst, $currentInvoiceId) {
            $product = $products[$line['product_id']] ?? null;
            if (! $product) {
                throw new RuntimeException('Invalid product selected.');
            }
            if (! $product->can_sell) {
                throw new RuntimeException('Product '.$product->name.' cannot be sold.');
            }

            if ($line['rate_per_kg'] === null || $line['rate_per_kg'] <= 0) {
                throw new RuntimeException('Rate per kg is required for all lines.');
            }

            if ($line['gst_rate_percent'] === null || ! in_array((float) $line['gst_rate_percent'], $allowedGst, true)) {
                throw new RuntimeException('Select a valid GST rate.');
            }

            $dispatchLine = $line['dispatch_line_id'] ? ($dispatchLines[$line['dispatch_line_id']] ?? null) : null;
            if ($dispatchLine) {
                if ($dispatchLine->product_id !== $product->id) {
                    throw new RuntimeException('Dispatch line product mismatch.');
                }
                if ($dispatchLine->invoice_id && $dispatchLine->invoice_id !== $currentInvoiceId) {
                    throw new RuntimeException('Dispatch line already linked to another invoice.');
                }
                $line['dispatch_id'] = $dispatchLine->dispatch_id;
            }

            if ($line['sale_mode'] === SalesInvoiceLine::MODE_PACK) {
                $packSize = $packSizes[$line['pack_size_id']] ?? null;
                if (! $packSize || $packSize->product_id !== $product->id) {
                    throw new RuntimeException('Selected pack size does not belong to the product.');
                }

                if ($line['pack_count'] <= 0) {
                    throw new RuntimeException('Pack count must be greater than zero.');
                }

                $line['pack_qty_snapshot'] = (float) $packSize->pack_qty;
                $line['pack_uom'] = $packSize->pack_uom;
                $line['computed_total_qty'] = round((float) $packSize->pack_qty * (int) $line['pack_count'], 3);
                $line['qty_bulk'] = null;
                $line['uom'] = $line['uom'] ?? $packSize->pack_uom;
            } else {
                if ($line['qty_bulk'] === null || $line['qty_bulk'] <= 0) {
                    throw new RuntimeException('Bulk quantity must be greater than zero.');
                }
                $line['pack_size_id'] = null;
                $line['pack_count'] = 0;
                $line['pack_qty_snapshot'] = null;
                $line['pack_uom'] = null;
                $line['computed_total_qty'] = $line['qty_bulk'];
                $line['uom'] = $line['uom'] ?? $product->uom;
            }

            $qtyUsed = (float) $line['computed_total_qty'];
            $rate = (float) $line['rate_per_kg'];
            $gstRate = (float) $line['gst_rate_percent'];

            $line['taxable_amount'] = round($qtyUsed * $rate, 2);
            $line['gst_amount'] = round($line['taxable_amount'] * $gstRate / 100, 2);
            $line['line_total'] = round($line['taxable_amount'] + $line['gst_amount'], 2);

            return $line;
        });
    }

    private function calculateTotals(Collection $lines): array
    {
        $subtotal = $lines->sum('taxable_amount');
        $totalGst = $lines->sum('gst_amount');

        return [
            'subtotal' => round($subtotal, 2),
            'total_gst' => round($totalGst, 2),
            'grand_total' => round($subtotal + $totalGst, 2),
        ];
    }

    private function persistLines(SalesInvoice $invoice, Collection $lines): void
    {
        $invoice->lines()->createMany($lines->map(function ($line) {
            return $line;
        })->all());
    }

    private function syncDispatchLinks(SalesInvoice $invoice, Collection $lines, array $oldDispatchLineIds = []): void
    {
        $newDispatchLineIds = $lines->pluck('dispatch_line_id')->filter()->unique()->values()->all();

        if (! empty($oldDispatchLineIds)) {
            $toDetach = array_diff($oldDispatchLineIds, $newDispatchLineIds);
            if (! empty($toDetach)) {
                DispatchLine::whereIn('id', $toDetach)->update(['invoice_id' => null]);
            }
        }

        if (! empty($newDispatchLineIds)) {
            DispatchLine::whereIn('id', $newDispatchLineIds)->update(['invoice_id' => $invoice->id]);
        }
    }

    private function recalculateFromStoredLines(SalesInvoice $invoice): array
    {
        $invoice->loadMissing('lines');

        $subtotal = $invoice->lines->sum('taxable_amount');
        $totalGst = $invoice->lines->sum('gst_amount');

        return [
            'subtotal' => round($subtotal, 2),
            'total_gst' => round($totalGst, 2),
            'grand_total' => round($subtotal + $totalGst, 2),
        ];
    }
}
