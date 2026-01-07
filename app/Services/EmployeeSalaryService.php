<?php

namespace App\Services;

use App\Models\EmployeeSalaryRate;
use Carbon\Carbon;

class EmployeeSalaryService
{
    public function getRateOnDate(int $employeeId, string $date): ?EmployeeSalaryRate
    {
        return EmployeeSalaryRate::query()
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->first();
    }

    public function getSalarySegmentsForMonth(int $employeeId, string $month): array
    {
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd = Carbon::parse($month . '-01')->endOfMonth();

        $rates = EmployeeSalaryRate::query()
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $monthEnd->toDateString())
            ->orderBy('effective_from')
            ->get();

        if ($rates->isEmpty()) {
            return [];
        }

        $segments = [];
        $rateCount = $rates->count();

        foreach ($rates as $index => $rate) {
            $segmentStart = Carbon::parse($rate->effective_from)->greaterThan($monthStart)
                ? Carbon::parse($rate->effective_from)
                : $monthStart->copy();

            $segmentEnd = $monthEnd->copy();
            if ($index + 1 < $rateCount) {
                $nextRateStart = Carbon::parse($rates[$index + 1]->effective_from)->subDay();
                if ($nextRateStart->lessThan($segmentEnd)) {
                    $segmentEnd = $nextRateStart;
                }
            }

            if ($segmentStart->greaterThan($monthEnd) || $segmentStart->greaterThan($segmentEnd)) {
                continue;
            }

            $segments[] = [
                'from' => $segmentStart->toDateString(),
                'to' => $segmentEnd->toDateString(),
                'salary_type' => $rate->salary_type,
                'rate_amount' => (float) $rate->rate_amount,
            ];
        }

        return $segments;
    }
}
