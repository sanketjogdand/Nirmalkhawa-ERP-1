<?php

namespace App\Livewire\EmploymentPeriod;

use App\Models\Employee;
use App\Models\EmployeeEmploymentPeriod;
use App\Services\EmploymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;

    public $title = 'Employment Periods';

    public int $employeeId;
    public ?Employee $employee = null;

    public bool $showForm = false;
    public ?int $periodId = null;
    public $start_date;
    public $end_date;
    public $remarks;

    public function mount(Employee $employee): void
    {
        $this->authorize('employment_period.manage');
        $this->employee = $employee->load('employmentPeriods');
        $this->employeeId = $this->employee->id;

        $this->showForm = (bool) request()->boolean('add');

        if (! $this->employee->employmentPeriods->isEmpty()) {
            app(EmploymentService::class)->syncEmployeeDates($this->employeeId);
            $this->employee->refresh();
        }
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
        if (! $this->showForm) {
            $this->resetForm();
        }
    }

    public function edit(int $periodId): void
    {
        $this->authorize('employment_period.manage');
        $period = EmployeeEmploymentPeriod::withTrashed()
            ->where('employee_id', $this->employeeId)
            ->findOrFail($periodId);

        $this->periodId = $period->id;
        $this->start_date = $period->start_date?->toDateString();
        $this->end_date = $period->end_date?->toDateString();
        $this->remarks = $period->remarks;
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset(['periodId', 'start_date', 'end_date', 'remarks']);
    }

    public function save(EmploymentService $employmentService): void
    {
        $this->authorize('employment_period.manage');
        $data = $this->validate($this->rules());

        try {
            $employmentService->assertNoOverlap(
                $this->employeeId,
                $data['start_date'],
                $data['end_date'] ?? null,
                $this->periodId
            );
        } catch (\RuntimeException $exception) {
            $this->addError('start_date', $exception->getMessage());
            return;
        }

        if ($this->periodId) {
            EmployeeEmploymentPeriod::withTrashed()
                ->where('id', $this->periodId)
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
        $this->employee = Employee::findOrFail($this->employeeId);
        $this->resetForm();
        $this->showForm = false;
    }

    public function delete(int $periodId, EmploymentService $employmentService): void
    {
        $this->authorize('employment_period.manage');
        EmployeeEmploymentPeriod::where('employee_id', $this->employeeId)
            ->where('id', $periodId)
            ->delete();
        $employmentService->syncEmployeeDates($this->employeeId);
        $this->employee = Employee::findOrFail($this->employeeId);
        session()->flash('success', 'Employment period deleted.');
    }

    public function render()
    {
        $periods = EmployeeEmploymentPeriod::query()
            ->where('employee_id', $this->employeeId)
            ->orderByDesc('start_date')
            ->get();

        return view('livewire.employment-period.view', [
            'periods' => $periods,
            'today' => now()->toDateString(),
        ])->with(['title_name' => $this->title ?? 'Employment Periods']);
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
