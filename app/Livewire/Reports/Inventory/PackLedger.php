<?php

namespace App\Livewire\Reports\Inventory;

use App\Livewire\Reports\BaseReport;
use App\Models\PackSize;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PackLedger extends BaseReport
{
    public string $title = 'Pack Ledger';
    public string $productId = '';
    public string $packSizeId = '';
    public array $productOptions = [];
    public array $packSizeOptions = [];

    protected string $dateField = 'po.txn_date';
    protected array $filterFields = ['productId', 'packSizeId'];

    protected function initFilters(): void
    {
        $this->productOptions = Product::orderBy('name')
            ->get()
            ->map(fn ($product) => ['value' => (string) $product->id, 'label' => $product->name])
            ->all();

        $this->packSizeOptions = PackSize::with('product')
            ->orderBy('pack_qty')
            ->get()
            ->map(function ($size) {
                $label = ($size->product->name ?? 'Product')." - {$size->pack_qty} {$size->pack_uom}";

                return ['value' => (string) $size->id, 'label' => $label];
            })
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'productId' => [
                'label' => 'Product',
                'options' => $this->productOptions,
            ],
            'packSizeId' => [
                'label' => 'Pack Size',
                'options' => $this->packSizeOptions,
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'operation' => 'Operation',
            'packs_in' => 'Packs In',
            'packs_out' => 'Packs Out',
            'running_balance' => 'Running Balance',
            'reference' => 'Reference',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('pack_operations_view as po')
            ->when($this->productId, fn ($q) => $q->where('po.product_id', $this->productId))
            ->when($this->packSizeId, fn ($q) => $q->where('po.pack_size_id', $this->packSizeId))
            ->selectRaw('po.txn_date as date')
            ->selectRaw('po.operation as operation')
            ->selectRaw('COALESCE(po.pack_count_in, 0) as packs_in')
            ->selectRaw('COALESCE(po.pack_count_out, 0) as packs_out')
            ->selectRaw('CONCAT(po.ref_table, "#", po.ref_id) as reference')
            ->orderBy('po.txn_date')
            ->orderBy('po.created_at');
    }

    protected function paginatedRows(): LengthAwarePaginator
    {
        $rows = $this->applyFilters($this->baseQuery())->paginate($this->perPage);
        $running = 0;

        return $rows->through(function ($row) use (&$running) {
            $running += (int) $row->packs_in - (int) $row->packs_out;

            return [
                'date' => $row->date,
                'operation' => $row->operation,
                'packs_in' => $row->packs_in,
                'packs_out' => $row->packs_out,
                'running_balance' => $running,
                'reference' => $row->reference,
            ];
        });
    }

    protected function exportRows(): array
    {
        $rows = $this->applyFilters($this->baseQuery())->get();
        $running = 0;

        return $rows->map(function ($row) use (&$running) {
            $running += (int) $row->packs_in - (int) $row->packs_out;

            return [
                'date' => $row->date,
                'operation' => $row->operation,
                'packs_in' => $row->packs_in,
                'packs_out' => $row->packs_out,
                'running_balance' => $running,
                'reference' => $row->reference,
            ];
        })->all();
    }
}
