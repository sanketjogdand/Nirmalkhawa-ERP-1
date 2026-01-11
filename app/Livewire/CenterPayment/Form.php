<?php

namespace App\Livewire\CenterPayment;

use App\Models\Center;
use App\Models\CenterPayment;
use App\Services\CenterBalanceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Center Payments';

    public ?int $paymentId = null;
    public $center_id;
    public $payment_date;
    public $payment_type = CenterPayment::TYPE_REGULAR;
    public $amount;
    public $payment_mode;
    public $company_account;
    public $reference_no;
    public $remarks;
    public $balancePayable = null;
    public bool $isLocked = false;

    public $centers = [];
    public $paymentModes = [];
    public $companyAccounts = [];

    public function mount($payment = null): void
    {
        $this->centers = Center::orderBy('name')->get();
        $this->paymentModes = DB::table('payment_modes')->orderBy('name')->pluck('name');
        $this->companyAccounts = DB::table('company_accounts')->orderBy('name')->pluck('name');

        if ($payment) {
            $record = CenterPayment::findOrFail($payment);
            $this->authorize('centerpayment.update');
            $this->paymentId = $record->id;
            $this->fill($record->only([
                'center_id',
                'payment_date',
                'payment_type',
                'amount',
                'payment_mode',
                'company_account',
                'reference_no',
                'remarks',
            ]));

            $this->payment_date = $this->payment_date ? $this->payment_date->toDateString() : null;
            $this->isLocked = (bool) $record->is_locked;
        } else {
            $this->authorize('centerpayment.create');
            $this->payment_date = now()->toDateString();
            $this->payment_type = CenterPayment::TYPE_REGULAR;
        }

        $this->refreshBalance();
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget('success');

        if (in_array($field, ['center_id', 'payment_date'], true)) {
            $this->refreshBalance();
        }
    }

    public function save()
    {
        $this->authorize($this->paymentId ? 'centerpayment.update' : 'centerpayment.create');

        if ($this->paymentId) {
            $record = CenterPayment::findOrFail($this->paymentId);
            if ($record->is_locked) {
                abort(403, 'Payment is locked and cannot be edited.');
            }
        }

        $data = $this->validate($this->rules());

        if ($this->paymentId) {
            CenterPayment::findOrFail($this->paymentId)->update($data);
            session()->flash('success', 'Payment updated.');
        } else {
            $data['created_by'] = Auth::id();
            CenterPayment::create($data);
            session()->flash('success', 'Payment recorded.');
            $this->resetForm();
        }

        return redirect()->route('center-payments.view');
    }

    public function render()
    {
        return view('livewire.center-payment.form')
            ->with(['title_name' => $this->title ?? 'Center Payments']);
    }

    private function rules(): array
    {
        return [
            'center_id' => ['required', 'exists:centers,id'],
            'payment_date' => ['required', 'date'],
            'payment_type' => ['required', Rule::in([CenterPayment::TYPE_ADVANCE, CenterPayment::TYPE_REGULAR])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'string', 'max:100', Rule::exists('payment_modes', 'name')],
            'company_account' => ['required', 'string', 'max:150', Rule::exists('company_accounts', 'name')],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function refreshBalance(): void
    {
        if (! $this->center_id) {
            $this->balancePayable = null;
            return;
        }

        $toDate = $this->payment_date ?: now()->toDateString();
        $balanceService = app(CenterBalanceService::class);
        $this->balancePayable = $balanceService->getNetPayableTillDate((int) $this->center_id, $toDate);
    }

    private function resetForm(): void
    {
        $this->reset([
            'center_id',
            'payment_date',
            'payment_type',
            'amount',
            'payment_mode',
            'company_account',
            'reference_no',
            'remarks',
        ]);
        $this->payment_date = now()->toDateString();
        $this->payment_type = CenterPayment::TYPE_REGULAR;
        $this->refreshBalance();
    }
}
