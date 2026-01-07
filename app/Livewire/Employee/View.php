<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Services\EmploymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Employees';
    public $perPage = 25;
    public $search = '';
    public $employmentFilter = 'active';

    public function mount(): void
    {
        $this->authorize('employee.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'perPage', 'employmentFilter'])) {
            $this->resetPage();
        }
    }

    public function deleteEmployee(int $employeeId): void
    {
        $this->authorize('employee.delete');
        $employee = Employee::findOrFail($employeeId);
        $employee->delete();
        session()->flash('success', 'Employee deleted.');
        $this->resetPage();
    }

    public function render(EmploymentService $employmentService)
    {
        $employees = $this->baseQuery($employmentService)
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.employee.view', [
            'employees' => $employees,
            'today' => now()->toDateString(),
        ])->with(['title_name' => $this->title ?? 'Employees']);
    }

    private function baseQuery(EmploymentService $employmentService)
    {
        $today = now()->toDateString();

        if ($this->employmentFilter === 'active') {
            $query = $employmentService->employedEmployeesOn($today);
        } elseif ($this->employmentFilter === 'resigned') {
            $query = Employee::query()->whereHas('employmentPeriods')
                ->whereDoesntHave('employmentPeriods', function ($sub) use ($today) {
                    $sub->whereDate('start_date', '<=', $today)
                        ->where(function ($inner) use ($today) {
                            $inner->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $today);
                        });
                });
        } else {
            $query = Employee::query();
        }

        $query->with('employmentPeriods');

        if ($this->search) {
            $search = '%' . $this->search . '%';
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', $search)
                    ->orWhere('employee_code', 'like', $search)
                    ->orWhere('mobile', 'like', $search);
            });
        }

        return $query;
    }
}
