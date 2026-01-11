<?php

namespace App\Livewire\Reports\Inventory;

use App\Livewire\Reports\BaseReport;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InventoryLedgerBulk extends BaseReport
{
    public string $title = 'Inventory Ledger (Bulk)';
    public string $productId = '';
    public array $productOptions = [];

    protected string $dateField = 'il.txn_date';
    protected array $filterFields = ['productId'];

    protected function initFilters(): void
    {
        $this->productOptions = Product::orderBy('name')
            ->get()
            ->map(fn ($product) => ['value' => (string) $product->id, 'label' => $product->name])
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'productId' => [
                'label' => 'Product',
                'options' => $this->productOptions,
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'txn_type' => 'Txn Type',
            'qty_in' => 'Qty In',
            'qty_out' => 'Qty Out',
            'running_balance' => 'Running Balance',
            'reference' => 'Reference',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('inventory_ledger_view as il')
            ->when($this->productId, fn ($q) => $q->where('il.product_id', $this->productId))
            ->selectRaw('il.txn_date as date')
            ->selectRaw('il.txn_type as txn_type')
            ->selectRaw('ROUND(COALESCE(il.qty_in, 0), 3) as qty_in')
            ->selectRaw('ROUND(COALESCE(il.qty_out, 0), 3) as qty_out')
            ->selectRaw('CONCAT(il.ref_table, "#", il.ref_id) as reference')
            ->orderBy('il.txn_date')
            ->orderBy('il.created_at');
    }

    protected function paginatedRows(): LengthAwarePaginator
    {
        $rows = $this->applyFilters($this->baseQuery())->paginate($this->perPage);
        $running = 0.0;

        return $rows->through(function ($row) use (&$running) {
            $running += (float) $row->qty_in - (float) $row->qty_out;

            return [
                'date' => $row->date,
                'txn_type' => $row->txn_type,
                'qty_in' => $row->qty_in,
                'qty_out' => $row->qty_out,
                'running_balance' => round($running, 3),
                'reference' => $row->reference,
            ];
        });
    }

    protected function exportRows(): array
    {
        $rows = $this->applyFilters($this->baseQuery())->get();
        $running = 0.0;

        return $rows->map(function ($row) use (&$running) {
            $running += (float) $row->qty_in - (float) $row->qty_out;

            return [
                'date' => $row->date,
                'txn_type' => $row->txn_type,
                'qty_in' => $row->qty_in,
                'qty_out' => $row->qty_out,
                'running_balance' => round($running, 3),
                'reference' => $row->reference,
            ];
        })->all();
    }
}
