<?php

namespace App\Livewire\Reports\ExpensesSuppliers;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class RcmExpenseReport extends BaseReport
{
    public string $title = 'RCM Expense Report';

    protected string $dateField = 'ge.expense_date';

    protected function columns(): array
    {
        return [
            'vendor' => 'Vendor',
            'amount' => 'Amount',
            'gst_rate' => 'GST Rate',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('general_expense_lines as gel')
            ->join('general_expenses as ge', 'ge.id', '=', 'gel.general_expense_id')
            ->leftJoin('suppliers as sv', 'sv.id', '=', 'gel.vendor_id')
            ->leftJoin('suppliers as gs', 'gs.id', '=', 'ge.supplier_id')
            ->whereNull('ge.deleted_at')
            ->whereNull('gel.deleted_at')
            ->where('gel.is_rcm_applicable', true)
            ->selectRaw('COALESCE(gel.vendor_name, sv.name, gs.name, "-") as vendor')
            ->selectRaw('ROUND(COALESCE(gel.total_amount, 0), 2) as amount')
            ->selectRaw('ROUND(COALESCE(gel.gst_rate, 0), 2) as gst_rate')
            ->orderBy('vendor');
    }
}
