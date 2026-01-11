<?php

namespace App\Livewire\Reports\SalesDispatch;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class ProductWiseSalesSummary extends BaseReport
{
    public string $title = 'Product-wise Sales Summary';

    protected string $dateField = 'si.invoice_date';

    protected function columns(): array
    {
        return [
            'product' => 'Product',
            'total_qty' => 'Total Qty',
            'total_value' => 'Total Value',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('sales_invoice_lines as sil')
            ->join('sales_invoices as si', 'si.id', '=', 'sil.sales_invoice_id')
            ->leftJoin('products as p', 'p.id', '=', 'sil.product_id')
            ->whereNull('si.deleted_at')
            ->selectRaw('COALESCE(p.name, "-") as product')
            ->selectRaw('ROUND(COALESCE(SUM(COALESCE(sil.computed_total_qty, sil.qty_bulk, 0)), 0), 3) as total_qty')
            ->selectRaw('ROUND(COALESCE(SUM(COALESCE(sil.line_total, 0)), 0), 2) as total_value')
            ->groupBy('p.name')
            ->orderBy('p.name');
    }
}
