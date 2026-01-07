<?php

namespace App\Livewire\EmployeePayroll;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use App\Services\PayrollService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Payroll';

    public ?int $payrollId = null;
    public $employee_id;
    public $payroll_month;
    public $advance_deduction = 0;
    public $other_additions = 0;
    public $other_deductions = 0;
    public $remarks;
    public bool $isLocked = false;

    public $employees = [];

    public $total_days_in_month = 0;
    public $present_days = 0;
    public $basic_pay = 0;
    public $incentives_total = 0;
    public $advance_outstanding = 0;
    public $net_pay = 0;
    public array $segments = [];

    public function mount($payroll = null): void
    {
        $this->employees = Employee::orderBy('name')->get();

        if ($payroll) {
            $record = EmployeePayroll::findOrFail($payroll);
            $this->authorize('payroll.update');
            $this->payrollId = $record->id;
            $this->fill($record->only([
                'employee_id',
                'payroll_month',
                'advance_deduction',
                'other_additions',
                'other_deductions',
                'remarks',
            ]));
            $this->isLocked = (bool) $record->is_locked;
        } else {
            $this->authorize('payroll.create');
            $this->payroll_month = now()->format('Y-m');
        }

        $this->refreshComputed(app(PayrollService::class));
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        if (in_array($field, ['employee_id', 'payroll_month', 'advance_deduction', 'other_additions', 'other_deductions'])) {
            $this->refreshComputed(app(PayrollService::class));
        }
    }

    public function save()
    {
        $this->authorize($this->payrollId ? 'payroll.update' : 'payroll.create');

        if ($this->payrollId) {
            $record = EmployeePayroll::findOrFail($this->payrollId);
            if ($record->is_locked) {
                abort(403, 'Payroll is locked and cannot be edited.');
            }
        }

        $data = $this->validate($this->rules());

        $computed = app(PayrollService::class)->recomputePayroll(
            $data['employee_id'],
            $data['payroll_month'],
            (float) $data['advance_deduction'],
            (float) $data['other_additions'],
            (float) $data['other_deductions']
        );

        $payload = [
            'employee_id' => $data['employee_id'],
            'payroll_month' => $data['payroll_month'],
            'total_days_in_month' => $computed['total_days_in_month'],
            'present_days' => $computed['present_days'],
            'basic_pay' => $computed['basic_pay'],
            'incentives_total' => $computed['incentives_total'],
            'advance_deduction' => $computed['advance_deduction'],
            'other_additions' => $computed['other_additions'],
            'other_deductions' => $computed['other_deductions'],
            'net_pay' => $computed['net_pay'],
            'remarks' => $data['remarks'] ?? null,
        ];

        if ($this->payrollId) {
            EmployeePayroll::where('id', $this->payrollId)->update($payload);
            session()->flash('success', 'Payroll updated.');
        } else {
            $payload['created_by'] = Auth::id();
            EmployeePayroll::create($payload);
            session()->flash('success', 'Payroll generated.');
            $this->reset(['employee_id', 'payroll_month', 'advance_deduction', 'other_additions', 'other_deductions', 'remarks']);
            $this->payroll_month = now()->format('Y-m');
        }

        return redirect()->route('employee-payrolls.view');
    }

    public function render()
    {
        return view('livewire.employee-payroll.form')
            ->with(['title_name' => $this->title ?? 'Payroll']);
    }

    private function refreshComputed(PayrollService $payrollService): void
    {
        if (! $this->employee_id || ! $this->payroll_month) {
            $this->segments = [];
            $this->total_days_in_month = 0;
            $this->present_days = 0;
            $this->basic_pay = 0;
            $this->incentives_total = 0;
            $this->advance_outstanding = 0;
            $this->net_pay = 0;
            return;
        }

        try {
            $computed = $payrollService->recomputePayroll(
                $this->employee_id,
                $this->payroll_month,
                (float) $this->advance_deduction,
                (float) $this->other_additions,
                (float) $this->other_deductions
            );

            $this->segments = $computed['segments'];
            $this->total_days_in_month = $computed['total_days_in_month'];
            $this->present_days = $computed['present_days'];
            $this->basic_pay = $computed['basic_pay'];
            $this->incentives_total = $computed['incentives_total'];
            $this->advance_outstanding = $computed['advance_outstanding'];
            $this->net_pay = $computed['net_pay'];
        } catch (\RuntimeException $exception) {
            $this->segments = [];
            $this->total_days_in_month = 0;
            $this->present_days = 0;
            $this->basic_pay = 0;
            $this->incentives_total = 0;
            $this->advance_outstanding = 0;
            $this->net_pay = 0;
            $this->addError('payroll_month', $exception->getMessage());
        }
    }

    private function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'payroll_month' => [
                'required',
                'date_format:Y-m',
                Rule::unique('employee_payrolls', 'payroll_month')->where('employee_id', $this->employee_id)->ignore($this->payrollId),
            ],
            'advance_deduction' => ['nullable', 'numeric', 'min:0'],
            'other_additions' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
