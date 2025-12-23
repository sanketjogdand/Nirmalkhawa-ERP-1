<?php

namespace App\Livewire\CustomerReceipt;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Customer Receipts';

    public ?int $receiptId = null;
    public $customer_id;
    public $receipt_date;
    public $amount;
    public $payment_mode;
    public $company_account;
    public $reference_no;
    public $remarks;

    public $customers = [];
    public $paymentModes = [];
    public $companyAccounts = [];

    public function mount($receipt = null): void
    {
        $this->customers = Customer::orderBy('name')->get();
        $this->paymentModes = DB::table('payment_modes')->orderBy('name')->pluck('name');
        $this->companyAccounts = DB::table('company_accounts')->orderBy('name')->pluck('name');

        if ($receipt) {
            $record = CustomerReceipt::findOrFail($receipt);
            $this->authorize('receipt.update');
            $this->receiptId = $record->id;
            $this->fill($record->only([
                'customer_id',
                'receipt_date',
                'amount',
                'payment_mode',
                'company_account',
                'reference_no',
                'remarks',
            ]));

            $this->receipt_date = $this->receipt_date ? $this->receipt_date->toDateString() : null;
        } else {
            $this->authorize('receipt.create');
            $this->receipt_date = now()->toDateString();
        }
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget('success');
    }

    public function save()
    {
        $this->authorize($this->receiptId ? 'receipt.update' : 'receipt.create');

        $data = $this->validate($this->rules());

        if ($this->receiptId) {
            CustomerReceipt::findOrFail($this->receiptId)->update($data);
            session()->flash('success', 'Receipt updated.');
        } else {
            $data['created_by'] = Auth::id();
            CustomerReceipt::create($data);
            session()->flash('success', 'Receipt recorded.');
            $this->resetForm();
        }

        return redirect()->route('customer-receipts.view');
    }

    public function render()
    {
        return view('livewire.customer-receipt.form')
            ->with(['title_name' => $this->title ?? 'Customer Receipts']);
    }

    private function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'receipt_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'max:100', Rule::exists('payment_modes', 'name')],
            'company_account' => ['required', 'string', 'max:150', Rule::exists('company_accounts', 'name')],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'customer_id',
            'receipt_date',
            'amount',
            'payment_mode',
            'company_account',
            'reference_no',
            'remarks',
        ]);
        $this->receipt_date = now()->toDateString();
    }

}
