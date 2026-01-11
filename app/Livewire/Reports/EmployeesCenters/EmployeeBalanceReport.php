<?php

namespace App\Livewire\Reports\EmployeesCenters;

use App\Livewire\Reports\BaseReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeBalanceReport extends BaseReport
{
    public string $title = 'Employee Balance Report';

    protected function columns(): array
    {
        return [
            'employee' => 'Employee',
            'total_payable' => 'Total Payable',
            'total_paid' => 'Total Paid',
            'balance' => 'Balance',
        ];
    }

    protected function applyFilters($query)
    {
        return $query;
    }

    protected function baseQuery()
    {
        $fromMonth = $this->fromDate ? Carbon::parse($this->fromDate)->format('Y-m') : null;
        $toMonth = $this->toDate ? Carbon::parse($this->toDate)->format('Y-m') : null;

        $payrollSub = DB::table('employee_payrolls as ep')
            ->whereNull('ep.deleted_at')
            ->when($fromMonth && $toMonth, fn ($q) => $q->whereBetween('ep.payroll_month', [$fromMonth, $toMonth]))
            ->selectRaw('ep.employee_id as employee_id, SUM(ep.net_pay) as total_payable')
            ->groupBy('ep.employee_id');

        $paymentsSub = DB::table('employee_payments as epay')
            ->whereNull('epay.deleted_at')
            ->when($this->fromDate, fn ($q) => $q->whereDate('epay.payment_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('epay.payment_date', '<=', $this->toDate))
            ->selectRaw('epay.employee_id as employee_id, SUM(epay.amount) as total_paid')
            ->groupBy('epay.employee_id');

        return DB::table('employees as e')
            ->leftJoinSub($payrollSub, 'payroll', 'payroll.employee_id', '=', 'e.id')
            ->leftJoinSub($paymentsSub, 'payments', 'payments.employee_id', '=', 'e.id')
            ->selectRaw('e.name as employee')
            ->selectRaw('ROUND(COALESCE(payroll.total_payable, 0), 2) as total_payable')
            ->selectRaw('ROUND(COALESCE(payments.total_paid, 0), 2) as total_paid')
            ->selectRaw('ROUND(COALESCE(payroll.total_payable, 0) - COALESCE(payments.total_paid, 0), 2) as balance')
            ->orderBy('e.name');
    }
}
