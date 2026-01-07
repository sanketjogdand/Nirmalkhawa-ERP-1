<?php

namespace App\Services;

use App\Models\EmployeeIncentive;
use Carbon\Carbon;

class IncentiveService
{
    public function sumIncentives(int $employeeId, string $month): float
    {
        $start = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        return (float) EmployeeIncentive::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('incentive_date', [$start, $end])
            ->sum('amount');
    }
}
