<?php

namespace App\Livewire\GeneralExpensePayment;

use App\Models\GeneralExpensePayment;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'General Expenses Payments';

    public ?int $paymentId = null;
    public $supplier_id;
    public $payment_date;
    public $amount;
    public $payment_mode;
    public $company_account;
    public $paid_to;
    public $remarks;

    public $suppliers = [];
    public $paymentModes = [];
    public $companyAccounts = [];

    public function mount($payment = null): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->paymentModes = DB::table('payment_modes')->orderBy('name')->pluck('name');
        $this->companyAccounts = DB::table('company_accounts')->orderBy('name')->pluck('name');

        if ($payment) {
            $record = GeneralExpensePayment::findOrFail($payment);
            $this->authorize('general_expense_payment.update');
            $this->paymentId = $record->id;
            $this->fill($record->only([
                'supplier_id',
                'payment_date',
                'amount',
                'payment_mode',
                'company_account',
                'paid_to',
                'remarks',
            ]));

            $this->payment_date = $this->payment_date ? $this->payment_date->toDateString() : null;
        } else {
            $this->authorize('general_expense_payment.create');
            $this->payment_date = now()->toDateString();
        }
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget('success');
    }

    public function save()
    {
        $this->authorize($this->paymentId ? 'general_expense_payment.update' : 'general_expense_payment.create');

        $data = $this->validate($this->rules());
        $data['general_expense_id'] = null;

        if ($this->paymentId) {
            $payment = GeneralExpensePayment::findOrFail($this->paymentId);
            if ($payment->is_locked) {
                session()->flash('danger', 'Locked payments cannot be edited.');
                return;
            }

            $payment->update($data);
            session()->flash('success', 'Payment updated.');
        } else {
            $data['created_by'] = Auth::id();
            GeneralExpensePayment::create($data);
            session()->flash('success', 'Payment recorded.');
            $this->resetForm();
        }

        return redirect()->route('general-expense-payments.view');
    }

    public function render()
    {
        return view('livewire.general-expense-payment.form')
            ->with(['title_name' => $this->title ?? 'General Expenses Payments']);
    }

    private function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['nullable', 'string', 'max:120', Rule::exists('payment_modes', 'name')],
            'company_account' => ['nullable', 'string', 'max:120', Rule::exists('company_accounts', 'name')],
            'paid_to' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'supplier_id',
            'payment_date',
            'amount',
            'payment_mode',
            'company_account',
            'paid_to',
            'remarks',
        ]);
        $this->payment_date = now()->toDateString();
    }
}
