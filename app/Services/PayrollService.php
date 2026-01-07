<?php

namespace App\Services;

use App\Models\EmployeeSalaryRate;
use Carbon\Carbon;

class PayrollService
{
    public function __construct(
        private readonly EmploymentService $employmentService,
        private readonly AttendanceService $attendanceService,
        private readonly EmployeeSalaryService $salaryService,
        private readonly IncentiveService $incentiveService,
        private readonly EmployeeBalanceService $balanceService,
    ) {
    }

    public function recomputePayroll(int $employeeId, string $month, float $advanceDeduction = 0, float $otherAdditions = 0, float $otherDeductions = 0): array
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();
        $totalDays = (int) $monthStart->daysInMonth;

        if (! $this->employmentService->hasAnyEmploymentOverlap($employeeId, $monthStart->toDateString(), $monthEnd->toDateString())) {
            throw new \RuntimeException('Employee is not employed during the selected month.');
        }

        $presentDays = $this->attendanceService->getPresentDays($employeeId, $month);
        $segments = $this->salaryService->getSalarySegmentsForMonth($employeeId, $month);

        $basicPay = 0.0;
        $segmentBreakdown = [];

        foreach ($segments as $segment) {
            $segmentPresent = $this->attendanceService->getPresentDaysByDateRange(
                $employeeId,
                $segment['from'],
                $segment['to']
            );

            $segmentBasic = 0.0;
            if ($segment['salary_type'] === EmployeeSalaryRate::TYPE_DAILY) {
                $segmentBasic = $segmentPresent * $segment['rate_amount'];
            } elseif ($segment['salary_type'] === EmployeeSalaryRate::TYPE_MONTHLY) {
                $segmentBasic = $totalDays > 0
                    ? ($segmentPresent / $totalDays) * $segment['rate_amount']
                    : 0.0;
            }

            $basicPay += $segmentBasic;

            $segmentBreakdown[] = [
                'from' => $segment['from'],
                'to' => $segment['to'],
                'salary_type' => $segment['salary_type'],
                'rate_amount' => $segment['rate_amount'],
                'present_days' => $segmentPresent,
                'segment_basic' => round($segmentBasic, 2),
            ];
        }

        $incentivesTotal = $this->incentiveService->sumIncentives($employeeId, $month);
        $advanceOutstanding = $this->balanceService->advanceOutstanding($employeeId);

        if ($advanceDeduction > $advanceOutstanding) {
            throw new \RuntimeException('Advance deduction exceeds outstanding advance.');
        }

        $netPay = $basicPay + $incentivesTotal + $otherAdditions - ($advanceDeduction + $otherDeductions);

        return [
            'month_start' => $monthStart->toDateString(),
            'month_end' => $monthEnd->toDateString(),
            'total_days_in_month' => $totalDays,
            'present_days' => $presentDays,
            'basic_pay' => round($basicPay, 2),
            'segments' => $segmentBreakdown,
            'incentives_total' => round($incentivesTotal, 2),
            'advance_outstanding' => round($advanceOutstanding, 2),
            'advance_deduction' => round($advanceDeduction, 2),
            'other_additions' => round($otherAdditions, 2),
            'other_deductions' => round($otherDeductions, 2),
            'net_pay' => round($netPay, 2),
        ];
    }
}
