<?php

namespace App\Livewire\GeneralExpensePayment;

use App\Models\GeneralExpensePayment;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'General Expenses Payments';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $supplierId;

    public $suppliers = [];

    public function mount(): void
    {
        $this->authorize('general_expense_payment.view');
        $this->suppliers = Supplier::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'supplierId'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function deletePayment(int $paymentId): void
    {
        $this->authorize('general_expense_payment.delete');
        $payment = GeneralExpensePayment::findOrFail($paymentId);

        if ($payment->is_locked) {
            session()->flash('danger', 'Locked payments cannot be deleted.');
            return;
        }

        $payment->delete();

        session()->flash('success', 'Payment deleted.');
        $this->resetPage();
    }

    public function lockPayment(int $paymentId): void
    {
        $this->authorize('general_expense_payment.lock');
        $payment = GeneralExpensePayment::findOrFail($paymentId);

        if ($payment->is_locked) {
            session()->flash('danger', 'Payment already locked.');
            return;
        }

        $payment->update([
            'is_locked' => true,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
        ]);

        session()->flash('success', 'Payment locked.');
    }

    public function unlockPayment(int $paymentId): void
    {
        $this->authorize('general_expense_payment.unlock');
        $payment = GeneralExpensePayment::findOrFail($paymentId);

        if (! $payment->is_locked) {
            session()->flash('danger', 'Payment already unlocked.');
            return;
        }

        $payment->update([
            'is_locked' => false,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        session()->flash('success', 'Payment unlocked.');
    }

    public function render()
    {
        $payments = GeneralExpensePayment::with(['supplier', 'lockedBy'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.general-expense-payment.view', [
            'payments' => $payments,
        ])->with(['title_name' => $this->title ?? 'General Expenses Payments']);
    }
}
