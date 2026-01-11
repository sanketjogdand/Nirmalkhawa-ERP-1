<?php

namespace App\Livewire\Reports\Inventory;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class CurrentStockSnapshotBulk extends BaseReport
{
    public string $title = 'Current Stock Snapshot (Bulk)';

    protected function columns(): array
    {
        return [
            'product' => 'Product',
            'opening' => 'Opening',
            'total_in' => 'Total In',
            'total_out' => 'Total Out',
            'balance' => 'Balance',
        ];
    }

    protected function applyFilters($query)
    {
        return $query;
    }

    protected function baseQuery()
    {
        $openingSub = DB::table('inventory_ledger_view as il')
            ->selectRaw('il.product_id, SUM(il.qty_in - il.qty_out) as opening')
            ->when($this->fromDate, fn ($q) => $q->whereDate('il.txn_date', '<', $this->fromDate))
            ->groupBy('il.product_id');

        $periodSub = DB::table('inventory_ledger_view as il')
            ->selectRaw('il.product_id, SUM(il.qty_in) as total_in, SUM(il.qty_out) as total_out')
            ->when($this->fromDate, fn ($q) => $q->whereDate('il.txn_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('il.txn_date', '<=', $this->toDate))
            ->groupBy('il.product_id');

        return DB::table('products as p')
            ->leftJoinSub($openingSub, 'opening', 'opening.product_id', '=', 'p.id')
            ->leftJoinSub($periodSub, 'period', 'period.product_id', '=', 'p.id')
            ->selectRaw('p.name as product')
            ->selectRaw('ROUND(COALESCE(opening.opening, 0), 3) as opening')
            ->selectRaw('ROUND(COALESCE(period.total_in, 0), 3) as total_in')
            ->selectRaw('ROUND(COALESCE(period.total_out, 0), 3) as total_out')
            ->selectRaw('ROUND(COALESCE(opening.opening, 0) + COALESCE(period.total_in, 0) - COALESCE(period.total_out, 0), 3) as balance')
            ->orderBy('p.name');
    }
}
