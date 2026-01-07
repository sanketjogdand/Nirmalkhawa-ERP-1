<?php

namespace App\Livewire\EmployeeSalaryRate;

use App\Models\Employee;
use App\Models\EmployeeSalaryRate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class View extends Component
{
    use AuthorizesRequests;

    public $title = 'Salary Rates';

    public int $employeeId;
    public $effective_from;
    public $salary_type = EmployeeSalaryRate::TYPE_MONTHLY;
    public $rate_amount;
    public $remarks;

    public function mount(int $employee): void
    {
        $this->authorize('salary_rate.view');
        $this->employeeId = $employee;
    }

    public function save(): void
    {
        $this->authorize('salary_rate.manage');
        $data = $this->validate($this->rules());
        $data['employee_id'] = $this->employeeId;
        $data['created_by'] = Auth::id();
        EmployeeSalaryRate::create($data);

        $this->reset(['effective_from', 'salary_type', 'rate_amount', 'remarks']);
        $this->salary_type = EmployeeSalaryRate::TYPE_MONTHLY;
        session()->flash('success', 'Salary rate added.');
    }

    public function delete(int $rateId): void
    {
        $this->authorize('salary_rate.manage');
        EmployeeSalaryRate::where('employee_id', $this->employeeId)
            ->where('id', $rateId)
            ->delete();
        session()->flash('success', 'Salary rate deleted.');
    }

    public function render()
    {
        $employee = Employee::findOrFail($this->employeeId);
        $rates = EmployeeSalaryRate::query()
            ->where('employee_id', $this->employeeId)
            ->orderByDesc('effective_from')
            ->get();

        return view('livewire.employee-salary-rate.view', [
            'employee' => $employee,
            'rates' => $rates,
        ])->with(['title_name' => $this->title ?? 'Salary Rates']);
    }

    private function rules(): array
    {
        return [
            'effective_from' => [
                'required',
                'date',
                Rule::unique('employee_salary_rates', 'effective_from')->where('employee_id', $this->employeeId),
            ],
            'salary_type' => ['required', Rule::in([EmployeeSalaryRate::TYPE_MONTHLY, EmployeeSalaryRate::TYPE_DAILY])],
            'rate_amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
