<?php

namespace App\Livewire\Reports\Management;

use App\Livewire\Reports\BaseReport;
use Illuminate\Support\Facades\DB;

class BackdatedEntryReport extends BaseReport
{
    public string $title = 'Backdated Entry Report';

    protected string $dateField = 'date';

    protected function columns(): array
    {
        return [
            'module' => 'Module',
            'reference' => 'Reference',
            'date' => 'Date',
            'created_at' => 'Created At',
        ];
    }

    protected function baseQuery()
    {
        $milk = DB::table('milk_intakes as mi')
            ->whereNull('mi.deleted_at')
            ->whereRaw('DATE(mi.created_at) > mi.date')
            ->selectRaw('"Milk Intake" as module')
            ->selectRaw('CONCAT("MI-", mi.id) as reference')
            ->selectRaw('mi.date as date')
            ->selectRaw('mi.created_at as created_at');

        $production = DB::table('production_batches as pb')
            ->whereNull('pb.deleted_at')
            ->whereRaw('DATE(pb.created_at) > pb.date')
            ->selectRaw('"Production" as module')
            ->selectRaw('CONCAT("PB-", pb.id) as reference')
            ->selectRaw('pb.date as date')
            ->selectRaw('pb.created_at as created_at');

        $purchases = DB::table('purchases as p')
            ->whereNull('p.deleted_at')
            ->whereRaw('DATE(p.created_at) > p.purchase_date')
            ->selectRaw('"Purchase" as module')
            ->selectRaw('COALESCE(p.supplier_bill_no, CONCAT("P-", p.id)) as reference')
            ->selectRaw('p.purchase_date as date')
            ->selectRaw('p.created_at as created_at');

        $sales = DB::table('sales_invoices as si')
            ->whereNull('si.deleted_at')
            ->whereRaw('DATE(si.created_at) > si.invoice_date')
            ->selectRaw('"Sales Invoice" as module')
            ->selectRaw('COALESCE(si.invoice_no, CONCAT("SI-", si.id)) as reference')
            ->selectRaw('si.invoice_date as date')
            ->selectRaw('si.created_at as created_at');

        $expenses = DB::table('general_expenses as ge')
            ->whereNull('ge.deleted_at')
            ->whereRaw('DATE(ge.created_at) > ge.expense_date')
            ->selectRaw('"General Expense" as module')
            ->selectRaw('COALESCE(ge.invoice_no, CONCAT("GE-", ge.id)) as reference')
            ->selectRaw('ge.expense_date as date')
            ->selectRaw('ge.created_at as created_at');

        $dispatches = DB::table('dispatches as d')
            ->whereNull('d.deleted_at')
            ->whereRaw('DATE(d.created_at) > d.dispatch_date')
            ->selectRaw('"Dispatch" as module')
            ->selectRaw('COALESCE(d.dispatch_no, CONCAT("D-", d.id)) as reference')
            ->selectRaw('d.dispatch_date as date')
            ->selectRaw('d.created_at as created_at');

        $adjustments = DB::table('stock_adjustments as sa')
            ->whereNull('sa.deleted_at')
            ->whereRaw('DATE(sa.created_at) > sa.adjustment_date')
            ->selectRaw('"Stock Adjustment" as module')
            ->selectRaw('CONCAT("SA-", sa.id) as reference')
            ->selectRaw('sa.adjustment_date as date')
            ->selectRaw('sa.created_at as created_at');

        $employeePayments = DB::table('employee_payments as ep')
            ->whereNull('ep.deleted_at')
            ->whereRaw('DATE(ep.created_at) > ep.payment_date')
            ->selectRaw('"Employee Payment" as module')
            ->selectRaw('CONCAT("EP-", ep.id) as reference')
            ->selectRaw('ep.payment_date as date')
            ->selectRaw('ep.created_at as created_at');

        $supplierPayments = DB::table('supplier_payments as sp')
            ->whereNull('sp.deleted_at')
            ->whereRaw('DATE(sp.created_at) > sp.payment_date')
            ->selectRaw('"Supplier Payment" as module')
            ->selectRaw('CONCAT("SP-", sp.id) as reference')
            ->selectRaw('sp.payment_date as date')
            ->selectRaw('sp.created_at as created_at');

        $centerPayments = DB::table('center_payments as cp')
            ->whereNull('cp.deleted_at')
            ->whereRaw('DATE(cp.created_at) > cp.payment_date')
            ->selectRaw('"Center Payment" as module')
            ->selectRaw('CONCAT("CP-", cp.id) as reference')
            ->selectRaw('cp.payment_date as date')
            ->selectRaw('cp.created_at as created_at');

        $union = $milk
            ->unionAll($production)
            ->unionAll($purchases)
            ->unionAll($sales)
            ->unionAll($expenses)
            ->unionAll($dispatches)
            ->unionAll($adjustments)
            ->unionAll($employeePayments)
            ->unionAll($supplierPayments)
            ->unionAll($centerPayments);

        return DB::query()
            ->fromSub($union, 't')
            ->select('module', 'reference', 'date', 'created_at')
            ->orderByDesc('date')
            ->orderByDesc('created_at');
    }
}
