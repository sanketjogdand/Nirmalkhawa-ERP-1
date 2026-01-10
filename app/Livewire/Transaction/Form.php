<?php

namespace App\Livewire\Transaction;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class Form extends Component
{
    use AuthorizesRequests;
    public $transactionId;
    public $transaction_date, $type, $other_type, $payment_mode, $debit_credit, $reference, $description, $gst_type, $amount, $gst_percent, $gst_amount, $total_amount, $paid_amount, $gstin, $previous_balance_amount, $final_total_amount;

    public $from_party_type, $from_party, $to_party_type, $to_party;

    public $accounts = [];
    public $vendors = [];
    public $customers = [];
    public $payment_modes = [];
    public $company_accounts = [];
    public $employees = [];
    public $transaction_types = [];

    public function mount($transaction = null)
    {
        $this->vendors = Supplier::orderBy('name')->get();
        $this->customers = Customer::orderBy('name')->get();
        $this->transaction_date = now()->toDateString();
        $this->payment_modes = DB::table('payment_modes')->get();
        $this->company_accounts = DB::table('company_accounts')->get();
        $this->employees = DB::table('employees')->get();
        $this->transaction_types = DB::table('transaction_types')->get();

        if ($transaction) {
            $this->authorize('transaction.update');
            $rec = Transaction::findOrFail($transaction);
            $this->transactionId = $rec->id;
            $this->fill($rec->toArray());
            $this->get_previous_balance();
            $this->fill($rec->toArray());
            $this->autoCalculateTotal();
        } else {
            $this->authorize('transaction.create');
        }
    }

    public function get_previous_balance()
    {
        $this->autoSetDebitCredit();

        $query = Transaction::query()
        ->where('from_party_type', $this->from_party_type)
        ->where('to_party_type', $this->to_party_type);

    if ($this->from_party_type !== 'Self') {
        $query->where('from_party', $this->from_party);
    }

    if ($this->to_party_type !== 'Self') {
        $query->where('to_party', $this->to_party);
    }

    $this->previous_balance_amount = $query
        ->selectRaw("
            CASE 
                WHEN SUM(amount) - SUM(COALESCE(paid_amount, 0)) < 0
                    THEN SUM(amount) - SUM(COALESCE(paid_amount, 0))
                WHEN SUM(amount) - SUM(COALESCE(paid_amount, 0)) < 1
                    THEN 0
                ELSE
                    SUM(amount) - SUM(COALESCE(paid_amount, 0))
            END AS balance
        ")
        ->value('balance');

        $this->payment_mode = $this->gst_type = $this->amount = $this->gst_percent = $this->gst_amount = $this->total_amount = $this->paid_amount = $this->gstin = $this->description = null;
        $this->final_total_amount = $this->previous_balance_amount;
    }

    private function autoSetDebitCredit(): void
    {
        $otherTypes = ['Vendor', 'Customer', 'Employee', 'Other'];

        if ($this->from_party_type === 'Self' && in_array($this->to_party_type, $otherTypes, true)) {
            $this->debit_credit = 'Outward';
            return;
        }

        if ($this->to_party_type === 'Self' && in_array($this->from_party_type, $otherTypes, true)) {
            $this->debit_credit = 'Inward';
        }

        if ($this->to_party_type === 'Self' && $this->from_party_type === 'Self') {
            $this->debit_credit = 'Contra';
        }
    }

    public function autoCalculateTotal()
    {
        $amount = floatval($this->amount ?? 0);
        $gst = floatval($this->gst_percent ?? 0);

        $this->gst_amount = round($amount * $gst / 100, 2);
        $this->final_total_amount = round($amount + $this->gst_amount, 2) + $this->previous_balance_amount;
        $this->total_amount = round($amount + $this->gst_amount, 2);
    }

    protected function rules()
    {
        return [

            'transaction_date' => 'required|date',
            'type' => 'nullable|string|max:50',
            'other_type' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:100',
            'from_party_type' => 'nullable|string',
            'from_party' => 'nullable|string',
            'to_party_type' => 'nullable|string',
            'to_party' => 'nullable|string',
            'payment_mode' => 'nullable|string',
            'debit_credit' => 'nullable|in:Inward,Outward,Contra',
            'gst_type' => 'nullable|string|max:20',
            'amount' => 'nullable|numeric|min:0.01',
            'gst_percent' => 'nullable|numeric',
            'gst_amount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'gstin' => 'nullable|string',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function save()
    {
        $this->authorize($this->transactionId ? 'transaction.update' : 'transaction.create');
        $data = $this->validate();
        if ($this->transactionId) {
            Transaction::where('id', $this->transactionId)->update($data);
            session()->flash('success', 'Transaction updated!');
        } else {
            Transaction::create($data);
            session()->flash('success', 'Transaction added!');
            $this->reset();
        }
        return redirect()->route('transactions.view');
    }

    public function render()
    {
        return view('livewire.transaction.form');
    }
}
