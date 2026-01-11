<?php

namespace App\Livewire\Reports\Inventory;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class PackStockSummary extends BaseReport
{
    public string $title = 'Pack Stock Summary';

    protected string $dateField = 'po.txn_date';

    protected function columns(): array
    {
        return [
            'product' => 'Product',
            'pack_size' => 'Pack Size',
            'packs_in' => 'Packs In',
            'packs_out' => 'Packs Out',
            'balance' => 'Balance',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('pack_operations_view as po')
            ->join('products as p', 'p.id', '=', 'po.product_id')
            ->join('pack_sizes as ps', 'ps.id', '=', 'po.pack_size_id')
            ->selectRaw('p.name as product')
            ->selectRaw('CONCAT(ps.pack_qty, " ", ps.pack_uom) as pack_size')
            ->selectRaw('COALESCE(SUM(po.pack_count_in), 0) as packs_in')
            ->selectRaw('COALESCE(SUM(po.pack_count_out), 0) as packs_out')
            ->selectRaw('COALESCE(SUM(po.pack_count_in - po.pack_count_out), 0) as balance')
            ->groupBy('p.name', 'ps.pack_qty', 'ps.pack_uom')
            ->orderBy('p.name')
            ->orderBy('ps.pack_qty');
    }
}
