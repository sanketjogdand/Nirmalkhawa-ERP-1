<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! DB::table('employees')->count()) {
            return;
        }

        $employees = DB::table('employees')
            ->select('id', 'joining_date', 'resignation_date')
            ->whereNotNull('joining_date')
            ->get();

        foreach ($employees as $employee) {
            $hasPeriods = DB::table('employee_employment_periods')
                ->where('employee_id', $employee->id)
                ->exists();

            if ($hasPeriods) {
                continue;
            }

            DB::table('employee_employment_periods')->insert([
                'employee_id' => $employee->id,
                'start_date' => $employee->joining_date,
                'end_date' => $employee->resignation_date,
                'remarks' => 'Backfilled from employee record',
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
        }

        $employeesWithPeriods = DB::table('employee_employment_periods')
            ->select('employee_id', DB::raw('MIN(start_date) as min_start'), DB::raw('MAX(end_date) as max_end'))
            ->whereNull('deleted_at')
            ->groupBy('employee_id')
            ->get();

        foreach ($employeesWithPeriods as $row) {
            DB::table('employees')
                ->where('id', $row->employee_id)
                ->update([
                    'joining_date' => $row->min_start,
                    'resignation_date' => $row->max_end,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: backfill is not reversible.
    }
};
