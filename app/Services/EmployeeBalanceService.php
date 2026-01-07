<?php

namespace App\Services;

use App\Models\EmployeePayment;
use App\Models\EmployeePayroll;

class EmployeeBalanceService
{
    public function advanceOutstanding(int $employeeId): float
    {
        $advances = (float) EmployeePayment::query()
            ->where('employee_id', $employeeId)
            ->where('payment_type', EmployeePayment::TYPE_ADVANCE)
            ->sum('amount');

        $deductions = (float) EmployeePayroll::query()
            ->where('employee_id', $employeeId)
            ->sum('advance_deduction');

        return round($advances - $deductions, 2);
    }

    public function balancePayable(int $employeeId): float
    {
        $payrollTotal = (float) EmployeePayroll::query()
            ->where('employee_id', $employeeId)
            ->sum('net_pay');

        $paid = (float) EmployeePayment::query()
            ->where('employee_id', $employeeId)
            ->sum('amount');

        return round($payrollTotal - $paid, 2);
    }
}
