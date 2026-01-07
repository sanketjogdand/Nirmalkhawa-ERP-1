<?php

namespace App\Livewire\EmployeeAttendance;

use App\Models\EmployeeAttendanceHeader;
use App\Models\EmployeeAttendanceLine;
use App\Services\AttendanceService;
use App\Services\EmploymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Attendance';

    public ?int $attendanceId = null;
    public $attendance_date;
    public $remarks;
    public bool $isLocked = false;

    public array $lines = [];

    public function mount($attendance = null): void
    {
        if ($attendance) {
            $record = EmployeeAttendanceHeader::with('lines.employee')->findOrFail($attendance);
            $this->authorize('attendance.update');
            $this->attendanceId = $record->id;
            $this->attendance_date = $record->attendance_date?->toDateString();
            $this->remarks = $record->remarks;
            $this->isLocked = (bool) $record->is_locked;
            $this->lines = $record->lines->map(function ($line) {
                return [
                    'employee_id' => $line->employee_id,
                    'employee_name' => $line->employee?->name,
                    'status' => $line->status,
                    'remarks' => $line->remarks,
                ];
            })->toArray();
        } else {
            $this->authorize('attendance.create');
            $this->attendance_date = now()->toDateString();
            $this->loadEmployeesForDate(app(EmploymentService::class));
        }
    }

    public function updatedAttendanceDate(): void
    {
        if ($this->attendanceId) {
            return;
        }

        $this->loadEmployeesForDate(app(EmploymentService::class));
    }

    public function markAllPresent(): void
    {
        foreach ($this->lines as $index => $line) {
            $this->lines[$index]['status'] = 'P';
        }
    }

    public function markAllAbsent(): void
    {
        foreach ($this->lines as $index => $line) {
            $this->lines[$index]['status'] = 'A';
        }
    }

    public function save(EmploymentService $employmentService, AttendanceService $attendanceService)
    {
        $this->authorize($this->attendanceId ? 'attendance.update' : 'attendance.create');

        if ($this->attendanceId) {
            $header = EmployeeAttendanceHeader::findOrFail($this->attendanceId);
            if ($header->is_locked) {
                abort(403, 'Attendance is locked and cannot be edited.');
            }
        }

        $data = $this->validate($this->rules());

        $linePayloads = [];
        foreach ($data['lines'] as $line) {
            if (! $employmentService->isEmployedOn($line['employee_id'], $data['attendance_date'])) {
                $this->addError('lines', 'All attendance entries must be within employment periods.');
                return;
            }
            $linePayloads[] = [
                'employee_id' => $line['employee_id'],
                'status' => $line['status'],
                'day_value' => $attendanceService->getDayValue($line['status']),
                'remarks' => $line['remarks'] ?? null,
            ];
        }

        DB::transaction(function () use ($data, $linePayloads) {
            if ($this->attendanceId) {
                EmployeeAttendanceHeader::where('id', $this->attendanceId)->update([
                    'attendance_date' => $data['attendance_date'],
                    'remarks' => $data['remarks'] ?? null,
                ]);
                EmployeeAttendanceLine::where('attendance_header_id', $this->attendanceId)->forceDelete();
                $headerId = $this->attendanceId;
            } else {
                $header = EmployeeAttendanceHeader::create([
                    'attendance_date' => $data['attendance_date'],
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => Auth::id(),
                ]);
                $headerId = $header->id;
            }

            foreach ($linePayloads as $payload) {
                EmployeeAttendanceLine::create([
                    'attendance_header_id' => $headerId,
                    ...$payload,
                ]);
            }
        });

        session()->flash('success', $this->attendanceId ? 'Attendance updated.' : 'Attendance saved.');
        return redirect()->route('employee-attendance.view');
    }

    public function render()
    {
        return view('livewire.employee-attendance.form')
            ->with(['title_name' => $this->title ?? 'Attendance']);
    }

    private function loadEmployeesForDate(EmploymentService $employmentService): void
    {
        $date = $this->attendance_date ?: now()->toDateString();
        $employees = $employmentService->employedEmployeesOn($date)
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->lines = $employees->map(function ($employee) {
            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'status' => 'P',
                'remarks' => null,
            ];
        })->toArray();
    }

    private function rules(): array
    {
        return [
            'attendance_date' => [
                'required',
                'date',
                Rule::unique('employee_attendance_headers', 'attendance_date')->ignore($this->attendanceId),
            ],
            'remarks' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.employee_id' => ['required', 'exists:employees,id'],
            'lines.*.status' => ['required', Rule::in(['P', 'A', 'H'])],
            'lines.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
