<?php

namespace App\Livewire\Reports\ExpensesSuppliers;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySummary extends BaseReport
{
    public string $title = 'Expense Category Summary';

    protected string $dateField = 'ge.expense_date';

    protected function columns(): array
    {
        return [
            'category' => 'Category',
            'total_amount' => 'Total Amount',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('general_expense_lines as gel')
            ->join('general_expenses as ge', 'ge.id', '=', 'gel.general_expense_id')
            ->leftJoin('expense_categories as ec', 'ec.id', '=', 'gel.category_id')
            ->whereNull('ge.deleted_at')
            ->whereNull('gel.deleted_at')
            ->selectRaw('COALESCE(ec.name, "-") as category')
            ->selectRaw('ROUND(COALESCE(SUM(gel.total_amount), 0), 2) as total_amount')
            ->groupBy('ec.name')
            ->orderBy('ec.name');
    }
}
