<?php

namespace App\Livewire\Reports\ExpensesSuppliers;

use App\Livewire\Reports\BaseReport;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierLedger extends BaseReport
{
    public string $title = 'Supplier Ledger';
    public string $supplierId = '';
    public array $supplierOptions = [];

    protected string $dateField = 'date';
    protected array $filterFields = ['supplierId'];

    protected function initFilters(): void
    {
        $this->supplierOptions = Supplier::orderBy('name')
            ->get()
            ->map(fn ($supplier) => ['value' => (string) $supplier->id, 'label' => $supplier->name])
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'supplierId' => [
                'label' => 'Supplier',
                'options' => $this->supplierOptions,
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'ref' => 'Reference',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'balance' => 'Balance',
        ];
    }

    protected function baseQuery()
    {
        $purchases = DB::table('purchases as p')
            ->whereNull('p.deleted_at')
            ->selectRaw('p.supplier_id as supplier_id')
            ->selectRaw('p.purchase_date as date')
            ->selectRaw('COALESCE(p.supplier_bill_no, CONCAT("Purchase ", p.id)) as ref')
            ->selectRaw('ROUND(COALESCE(p.grand_total, 0), 2) as debit')
            ->selectRaw('0 as credit')
            ->selectRaw('p.created_at as created_at');

        $grns = DB::table('grns as g')
            ->whereNull('g.deleted_at')
            ->selectRaw('g.supplier_id as supplier_id')
            ->selectRaw('g.grn_date as date')
            ->selectRaw('CONCAT("GRN ", g.id) as ref')
            ->selectRaw('0 as debit')
            ->selectRaw('0 as credit')
            ->selectRaw('g.created_at as created_at');

        $expenseTotals = DB::table('general_expense_lines as gel')
            ->whereNull('gel.deleted_at')
            ->selectRaw('gel.general_expense_id as general_expense_id, SUM(gel.total_amount) as total_amount')
            ->groupBy('gel.general_expense_id');

        $expenses = DB::table('general_expenses as ge')
            ->leftJoinSub($expenseTotals, 'totals', 'totals.general_expense_id', '=', 'ge.id')
            ->whereNull('ge.deleted_at')
            ->whereNotNull('ge.supplier_id')
            ->selectRaw('ge.supplier_id as supplier_id')
            ->selectRaw('ge.expense_date as date')
            ->selectRaw('COALESCE(ge.invoice_no, CONCAT("Expense ", ge.id)) as ref')
            ->selectRaw('ROUND(COALESCE(totals.total_amount, 0), 2) as debit')
            ->selectRaw('0 as credit')
            ->selectRaw('ge.created_at as created_at');

        $supplierPayments = DB::table('supplier_payments as sp')
            ->whereNull('sp.deleted_at')
            ->selectRaw('sp.supplier_id as supplier_id')
            ->selectRaw('sp.payment_date as date')
            ->selectRaw('CONCAT("Supplier Payment ", sp.id) as ref')
            ->selectRaw('0 as debit')
            ->selectRaw('ROUND(COALESCE(sp.amount, 0), 2) as credit')
            ->selectRaw('sp.created_at as created_at');

        $expensePayments = DB::table('general_expense_payments as gep')
            ->whereNull('gep.deleted_at')
            ->selectRaw('gep.supplier_id as supplier_id')
            ->selectRaw('gep.payment_date as date')
            ->selectRaw('CONCAT("Expense Payment ", gep.id) as ref')
            ->selectRaw('0 as debit')
            ->selectRaw('ROUND(COALESCE(gep.amount, 0), 2) as credit')
            ->selectRaw('gep.created_at as created_at');

        $ledger = $purchases
            ->unionAll($grns)
            ->unionAll($expenses)
            ->unionAll($supplierPayments)
            ->unionAll($expensePayments);

        return DB::query()
            ->fromSub($ledger, 'ledger')
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->select('date', 'ref', 'debit', 'credit', 'created_at')
            ->orderBy('date')
            ->orderBy('created_at');
    }

    protected function paginatedRows(): LengthAwarePaginator
    {
        $rows = $this->applyFilters($this->baseQuery())->paginate($this->perPage);
        $running = 0.0;

        return $rows->through(function ($row) use (&$running) {
            $running += (float) $row->debit - (float) $row->credit;

            return [
                'date' => $row->date,
                'ref' => $row->ref,
                'debit' => $row->debit,
                'credit' => $row->credit,
                'balance' => round($running, 2),
            ];
        });
    }

    protected function exportRows(): array
    {
        $rows = $this->applyFilters($this->baseQuery())->get();
        $running = 0.0;

        return $rows->map(function ($row) use (&$running) {
            $running += (float) $row->debit - (float) $row->credit;

            return [
                'date' => $row->date,
                'ref' => $row->ref,
                'debit' => $row->debit,
                'credit' => $row->credit,
                'balance' => round($running, 2),
            ];
        })->all();
    }
}
