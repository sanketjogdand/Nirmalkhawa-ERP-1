<?php

namespace App\Livewire\SupplierPayment;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Supplier Payments';

    public ?int $paymentId = null;
    public $supplier_id;
    public $payment_date;
    public $amount;
    public $payment_mode;
    public $company_account;
    public $reference_no;
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
            $record = SupplierPayment::findOrFail($payment);
            $this->authorize('supplierpayment.update');
            $this->paymentId = $record->id;
            $this->fill($record->only([
                'supplier_id',
                'payment_date',
                'amount',
                'payment_mode',
                'company_account',
                'reference_no',
                'remarks',
            ]));

            $this->payment_date = $this->payment_date ? $this->payment_date->toDateString() : null;
        } else {
            $this->authorize('supplierpayment.create');
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
        $this->authorize($this->paymentId ? 'supplierpayment.update' : 'supplierpayment.create');

        $data = $this->validate($this->rules());

        if ($this->paymentId) {
            SupplierPayment::findOrFail($this->paymentId)->update($data);
            session()->flash('success', 'Payment updated.');
        } else {
            $data['created_by'] = Auth::id();
            SupplierPayment::create($data);
            session()->flash('success', 'Payment recorded.');
            $this->resetForm();
        }

        return redirect()->route('supplier-payments.view');
    }

    public function render()
    {
        $supplierStats = null;

        if ($this->supplier_id) {
            $entitled = Purchase::where('supplier_id', $this->supplier_id)->sum('grand_total');
            $paid = SupplierPayment::where('supplier_id', $this->supplier_id)->sum('amount');
            $supplierStats = [
                'entitled' => $entitled,
                'paid' => $paid,
                'outstanding' => $entitled - $paid,
            ];
        }

        return view('livewire.supplier-payment.form', [
            'supplierStats' => $supplierStats,
        ])->with(['title_name' => $this->title ?? 'Supplier Payments']);
    }

    private function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'payment_date' => ['required', 'date'],
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
            'supplier_id',
            'payment_date',
            'amount',
            'payment_mode',
            'company_account',
            'reference_no',
            'remarks',
        ]);
        $this->payment_date = now()->toDateString();
    }
}
