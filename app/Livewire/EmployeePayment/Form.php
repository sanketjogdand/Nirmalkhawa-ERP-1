<?php

namespace App\Livewire\EmployeePayment;

use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Services\EmployeeBalanceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Employee Payments';

    public ?int $paymentId = null;
    public $employee_id;
    public $payment_date;
    public $payment_type = EmployeePayment::TYPE_ADVANCE;
    public $amount;
    public $payment_mode;
    public $company_account;
    public $remarks;
    public bool $isLocked = false;

    public $employees = [];
    public $advanceOutstanding = null;
    public $balancePayable = null;
    public $paymentModes = [];
    public $companyAccounts = [];

    public function mount($payment = null): void
    {
        $this->employees = Employee::orderBy('name')->get();
        $this->paymentModes = DB::table('payment_modes')->orderBy('name')->pluck('name');
        $this->companyAccounts = DB::table('company_accounts')->orderBy('name')->pluck('name');

        if ($payment) {
            $record = EmployeePayment::findOrFail($payment);
            $this->authorize('employee_payment.update');
            $this->paymentId = $record->id;
            $this->fill($record->only([
                'employee_id',
                'payment_date',
                'payment_type',
                'amount',
                'payment_mode',
                'company_account',
                'remarks',
            ]));
            $this->payment_date = $record->payment_date?->toDateString();
            $this->isLocked = (bool) $record->is_locked;
        } else {
            $this->authorize('employee_payment.create');
            $this->payment_date = now()->toDateString();
        }

        $this->refreshBalances(app(EmployeeBalanceService::class));
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget('success');

        if ($field === 'employee_id') {
            $this->refreshBalances(app(EmployeeBalanceService::class));
        }
    }

    public function save()
    {
        $this->authorize($this->paymentId ? 'employee_payment.update' : 'employee_payment.create');

        if ($this->paymentId) {
            $record = EmployeePayment::findOrFail($this->paymentId);
            if ($record->is_locked) {
                abort(403, 'Payment is locked and cannot be edited.');
            }
        }

        $data = $this->validate($this->rules());

        if ($this->paymentId) {
            EmployeePayment::where('id', $this->paymentId)->update($data);
            session()->flash('success', 'Payment updated.');
        } else {
            $data['created_by'] = Auth::id();
            EmployeePayment::create($data);
            session()->flash('success', 'Payment recorded.');
            $this->resetForm();
        }

        $this->refreshBalances(app(EmployeeBalanceService::class));

        return redirect()->route('employee-payments.view');
    }

    public function render()
    {
        return view('livewire.employee-payment.form')
            ->with(['title_name' => $this->title ?? 'Employee Payments']);
    }

    private function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'payment_date' => ['required', 'date'],
            'payment_type' => ['required', Rule::in([EmployeePayment::TYPE_ADVANCE, EmployeePayment::TYPE_SALARY, EmployeePayment::TYPE_OTHER])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['nullable', 'string', 'max:100', Rule::exists('payment_modes', 'name')],
            'company_account' => ['nullable', 'string', 'max:150', Rule::exists('company_accounts', 'name')],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function refreshBalances(EmployeeBalanceService $balanceService): void
    {
        if (! $this->employee_id) {
            $this->advanceOutstanding = null;
            $this->balancePayable = null;
            return;
        }

        $this->advanceOutstanding = $balanceService->advanceOutstanding($this->employee_id);
        $this->balancePayable = $balanceService->balancePayable($this->employee_id);
    }

    private function resetForm(): void
    {
        $this->reset([
            'employee_id',
            'payment_date',
            'payment_type',
            'amount',
            'payment_mode',
            'company_account',
            'remarks',
        ]);
        $this->payment_date = now()->toDateString();
        $this->payment_type = EmployeePayment::TYPE_ADVANCE;
    }
}
