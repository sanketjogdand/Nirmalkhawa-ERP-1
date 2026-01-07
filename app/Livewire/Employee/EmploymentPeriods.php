<?php

namespace App\Livewire\Employee;

use App\Models\EmployeeEmploymentPeriod;
use App\Services\EmploymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class EmploymentPeriods extends Component
{
    use AuthorizesRequests;

    public int $employeeId;
    public ?int $periodId = null;
    public $start_date;
    public $end_date;
    public $remarks;

    public function mount(int $employeeId): void
    {
        $this->authorize('employment_period.manage');
        $this->employeeId = $employeeId;
    }

    public function edit(int $periodId): void
    {
        $this->authorize('employment_period.manage');
        $period = EmployeeEmploymentPeriod::where('employee_id', $this->employeeId)->findOrFail($periodId);
        $this->periodId = $period->id;
        $this->start_date = $period->start_date?->toDateString();
        $this->end_date = $period->end_date?->toDateString();
        $this->remarks = $period->remarks;
    }

    public function resetForm(): void
    {
        $this->reset(['periodId', 'start_date', 'end_date', 'remarks']);
    }

    public function save(EmploymentService $employmentService): void
    {
        $this->authorize('employment_period.manage');
        $data = $this->validate($this->rules());

        $employmentService->assertNoOverlap(
            $this->employeeId,
            $data['start_date'],
            $data['end_date'] ?? null,
            $this->periodId
        );

        if ($this->periodId) {
            EmployeeEmploymentPeriod::where('id', $this->periodId)
                ->where('employee_id', $this->employeeId)
                ->update($data);
            session()->flash('success', 'Employment period updated.');
        } else {
            $data['employee_id'] = $this->employeeId;
            $data['created_by'] = Auth::id();
            EmployeeEmploymentPeriod::create($data);
            session()->flash('success', 'Employment period added.');
        }

        $employmentService->syncEmployeeDates($this->employeeId);
        $this->resetForm();
    }

    public function delete(int $periodId, EmploymentService $employmentService): void
    {
        $this->authorize('employment_period.manage');
        EmployeeEmploymentPeriod::where('employee_id', $this->employeeId)
            ->where('id', $periodId)
            ->delete();
        $employmentService->syncEmployeeDates($this->employeeId);
        session()->flash('success', 'Employment period deleted.');
    }

    public function render()
    {
        $periods = EmployeeEmploymentPeriod::query()
            ->where('employee_id', $this->employeeId)
            ->orderByDesc('start_date')
            ->get();

        return view('livewire.employee.employment-periods', [
            'periods' => $periods,
        ]);
    }

    private function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
