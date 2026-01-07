<?php

namespace App\Services;

use App\Models\EmployeeAttendanceLine;
use Carbon\Carbon;

class AttendanceService
{
    public function getDayValue(string $status): float
    {
        return match ($status) {
            'P' => 1.0,
            'H' => 0.5,
            default => 0.0,
        };
    }

    public function getPresentDays(int $employeeId, string $month): float
    {
        $start = Carbon::parse($month . '-01')->startOfMonth()->toDateString();
        $end = Carbon::parse($month . '-01')->endOfMonth()->toDateString();

        return (float) EmployeeAttendanceLine::query()
            ->where('employee_id', $employeeId)
            ->whereHas('header', function ($query) use ($start, $end) {
                $query->whereBetween('attendance_date', [$start, $end]);
            })
            ->sum('day_value');
    }

    public function getPresentDaysByDateRange(int $employeeId, string $from, string $to): float
    {
        return (float) EmployeeAttendanceLine::query()
            ->where('employee_id', $employeeId)
            ->whereHas('header', function ($query) use ($from, $to) {
                $query->whereBetween('attendance_date', [$from, $to]);
            })
            ->sum('day_value');
    }
}
