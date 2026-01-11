<?php

namespace App\Livewire\Reports\GstCompliance;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class GstInputSummary extends BaseReport
{
    public string $title = 'GST Input Summary';

    protected string $dateField = 'date';

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
        $purchaseLines = DB::table('purchase_lines as pl')
            ->join('purchases as p', 'p.id', '=', 'pl.purchase_id')
            ->whereNull('p.deleted_at')
            ->selectRaw('pl.gst_rate_percent as gst_rate')
            ->selectRaw('pl.taxable_amount as taxable')
            ->selectRaw('pl.gst_amount as gst')
            ->selectRaw('p.purchase_date as date');

        $expenseLines = DB::table('general_expense_lines as gel')
            ->join('general_expenses as ge', 'ge.id', '=', 'gel.general_expense_id')
            ->whereNull('ge.deleted_at')
            ->whereNull('gel.deleted_at')
            ->selectRaw('gel.gst_rate as gst_rate')
            ->selectRaw('gel.taxable_amount as taxable')
            ->selectRaw('gel.gst_amount as gst')
            ->selectRaw('ge.expense_date as date');

        $union = $purchaseLines->unionAll($expenseLines);

        return DB::query()
            ->fromSub($union, 't')
            ->selectRaw('ROUND(COALESCE(t.gst_rate, 0), 2) as gst_rate')
            ->selectRaw('ROUND(COALESCE(SUM(t.taxable), 0), 2) as taxable')
            ->selectRaw('ROUND(COALESCE(SUM(t.gst), 0), 2) as gst')
            ->groupBy('t.gst_rate')
            ->orderBy('t.gst_rate');
    }
}
