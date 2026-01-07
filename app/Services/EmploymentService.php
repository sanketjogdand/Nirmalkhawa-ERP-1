<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeEmploymentPeriod;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EmploymentService
{
    public function isEmployedOn(int $employeeId, string $date): bool
    {
        return EmployeeEmploymentPeriod::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->exists();
    }

    public function employedEmployeesOn(string $date): Builder
    {
        return Employee::query()->whereHas('employmentPeriods', function (Builder $query) use ($date) {
            $query->whereDate('start_date', '<=', $date)
                ->where(function (Builder $inner) use ($date) {
                    $inner->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $date);
                });
        });
    }

    public function hasAnyEmploymentOverlap(int $employeeId, string $from, string $to): bool
    {
        return EmployeeEmploymentPeriod::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $to)
            ->where(function (Builder $query) use ($from) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $from);
            })
            ->exists();
    }

    public function assertNoOverlap(int $employeeId, string $startDate, ?string $endDate = null, ?int $ignoreId = null): void
    {
        $end = $endDate ? Carbon::parse($endDate) : Carbon::create(9999, 12, 31);
        $start = Carbon::parse($startDate);

        $query = EmployeeEmploymentPeriod::query()
            ->where('employee_id', $employeeId)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->where(function (Builder $builder) use ($start) {
                $builder->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $start->toDateString());
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new \RuntimeException('Employment period overlaps with an existing period.');
        }
    }

    public function syncEmployeeDates(int $employeeId): void
    {
        $periods = EmployeeEmploymentPeriod::query()
            ->where('employee_id', $employeeId)
            ->get();

        if ($periods->isEmpty()) {
            Employee::where('id', $employeeId)->update([
                'joining_date' => null,
                'resignation_date' => null,
            ]);
            return;
        }

        $joiningDate = $periods->min('start_date');
        $hasOpenPeriod = $periods->contains(fn ($period) => $period->end_date === null);
        $resignationDate = $hasOpenPeriod ? null : $periods->max('end_date');

        Employee::where('id', $employeeId)->update([
            'joining_date' => $joiningDate,
            'resignation_date' => $resignationDate,
        ]);
    }
}
