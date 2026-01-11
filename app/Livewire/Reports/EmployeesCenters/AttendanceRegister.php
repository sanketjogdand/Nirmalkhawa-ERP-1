<?php

namespace App\Livewire\Reports\EmployeesCenters;

use App\Livewire\Reports\BaseReport;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class AttendanceRegister extends BaseReport
{
    public string $title = 'Attendance Register';
    public string $employeeId = '';
    public array $employeeOptions = [];

    protected string $dateField = 'eah.attendance_date';
    protected array $filterFields = ['employeeId'];

    protected function initFilters(): void
    {
        $this->employeeOptions = Employee::orderBy('name')
            ->get()
            ->map(fn ($employee) => ['value' => (string) $employee->id, 'label' => $employee->name])
            ->all();
    }

    protected function filterConfig(): array
    {
        return [
            'employeeId' => [
                'label' => 'Employee',
                'options' => $this->employeeOptions,
            ],
        ];
    }

    protected function columns(): array
    {
        return [
            'date' => 'Date',
            'status' => 'Status',
            'day_value' => 'Day Value',
        ];
    }

    protected function baseQuery()
    {
        return DB::table('employee_attendance_lines as eal')
            ->join('employee_attendance_headers as eah', 'eah.id', '=', 'eal.attendance_header_id')
            ->whereNull('eal.deleted_at')
            ->whereNull('eah.deleted_at')
            ->when($this->employeeId, fn ($q) => $q->where('eal.employee_id', $this->employeeId))
            ->selectRaw('eah.attendance_date as date')
            ->selectRaw('eal.status as status')
            ->selectRaw('COALESCE(eal.day_value, 0) as day_value')
            ->orderByDesc('eah.attendance_date');
    }
}
