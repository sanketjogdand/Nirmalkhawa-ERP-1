<?php

namespace App\Livewire\Reports\EmployeesCenters;

use App\Livewire\Reports\BaseReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryRegister extends BaseReport
{
    public string $title = 'Salary Register';

    protected function columns(): array
    {
        return [
            'employee' => 'Employee',
            'month' => 'Month',
            'basic' => 'Basic',
            'incentives' => 'Incentives',
            'deductions' => 'Deductions',
            'net_pay' => 'Net Pay',
        ];
    }

    protected function applyFilters($query)
    {
        $fromMonth = $this->fromDate ? Carbon::parse($this->fromDate)->format('Y-m') : null;
        $toMonth = $this->toDate ? Carbon::parse($this->toDate)->format('Y-m') : null;

        return $query->when($fromMonth && $toMonth, fn ($q) => $q->whereBetween('ep.payroll_month', [$fromMonth, $toMonth]));
    }

    protected function baseQuery()
    {
        return DB::table('employee_payrolls as ep')
            ->join('employees as e', 'e.id', '=', 'ep.employee_id')
            ->whereNull('ep.deleted_at')
            ->selectRaw('e.name as employee')
            ->selectRaw('ep.payroll_month as month')
            ->selectRaw('ROUND(COALESCE(ep.basic_pay, 0), 2) as basic')
            ->selectRaw('ROUND(COALESCE(ep.incentives_total, 0), 2) as incentives')
            ->selectRaw('ROUND(COALESCE(ep.advance_deduction, 0) + COALESCE(ep.other_deductions, 0), 2) as deductions')
            ->selectRaw('ROUND(COALESCE(ep.net_pay, 0), 2) as net_pay')
            ->orderByDesc('ep.payroll_month')
            ->orderBy('e.name');
    }
}
