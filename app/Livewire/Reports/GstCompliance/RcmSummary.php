<?php

namespace App\Livewire\Reports\GstCompliance;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class RcmSummary extends BaseReport
{
    public string $title = 'RCM Summary';

    protected string $dateField = 'ge.expense_date';

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
        return DB::table('general_expense_lines as gel')
            ->join('general_expenses as ge', 'ge.id', '=', 'gel.general_expense_id')
            ->whereNull('ge.deleted_at')
            ->whereNull('gel.deleted_at')
            ->where('gel.is_rcm_applicable', true)
            ->selectRaw('ROUND(COALESCE(gel.gst_rate, 0), 2) as gst_rate')
            ->selectRaw('ROUND(COALESCE(SUM(gel.taxable_amount), 0), 2) as taxable')
            ->selectRaw('ROUND(COALESCE(SUM(gel.gst_amount), 0), 2) as gst')
            ->groupBy('gel.gst_rate')
            ->orderBy('gel.gst_rate');
    }
}
