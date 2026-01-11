<?php

namespace App\Livewire\Reports\GstCompliance;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class GstOutputSummary extends BaseReport
{
    public string $title = 'GST Output Summary';

    protected string $dateField = 'si.invoice_date';

    protected function columns(): array
    {
        return [
            'gst_rate' => 'GST Rate',
            'taxable' => 'Taxable',
            'gst' => 'GST',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('sales_invoice_lines as sil')
            ->join('sales_invoices as si', 'si.id', '=', 'sil.sales_invoice_id')
            ->whereNull('si.deleted_at')
            ->selectRaw('ROUND(COALESCE(sil.gst_rate_percent, 0), 2) as gst_rate')
            ->selectRaw('ROUND(COALESCE(SUM(sil.taxable_amount), 0), 2) as taxable')
            ->selectRaw('ROUND(COALESCE(SUM(sil.gst_amount), 0), 2) as gst')
            ->groupBy('sil.gst_rate_percent')
            ->orderBy('sil.gst_rate_percent');
    }
}
