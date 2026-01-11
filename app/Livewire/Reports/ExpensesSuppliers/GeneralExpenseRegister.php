<?php

namespace App\Livewire\Reports\ExpensesSuppliers;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class GeneralExpenseRegister extends BaseReport
{
    public string $title = 'General Expense Register';

    protected string $dateField = 'ge.expense_date';

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'category' => 'Category',
            'vendor' => 'Vendor',
            'taxable' => 'Taxable',
            'gst' => 'GST',
            'total' => 'Total',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('general_expense_lines as gel')
            ->join('general_expenses as ge', 'ge.id', '=', 'gel.general_expense_id')
            ->leftJoin('expense_categories as ec', 'ec.id', '=', 'gel.category_id')
            ->leftJoin('suppliers as sv', 'sv.id', '=', 'gel.vendor_id')
            ->leftJoin('suppliers as gs', 'gs.id', '=', 'ge.supplier_id')
            ->whereNull('ge.deleted_at')
            ->whereNull('gel.deleted_at')
            ->selectRaw('ge.expense_date as date')
            ->selectRaw('COALESCE(ec.name, "-") as category')
            ->selectRaw('COALESCE(gel.vendor_name, sv.name, gs.name, "-") as vendor')
            ->selectRaw('ROUND(COALESCE(gel.taxable_amount, 0), 2) as taxable')
            ->selectRaw('ROUND(COALESCE(gel.gst_amount, 0), 2) as gst')
            ->selectRaw('ROUND(COALESCE(gel.total_amount, 0), 2) as total')
            ->orderByDesc('ge.expense_date');
    }
}
