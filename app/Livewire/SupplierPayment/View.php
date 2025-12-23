<?php

namespace App\Livewire\SupplierPayment;

use App\Models\DispatchDeliveryExpense;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Supplier Payments';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $supplierId;

    public $suppliers = [];

    public function mount(): void
    {
        $this->authorize('supplierpayment.view');
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
        $this->authorize('supplierpayment.delete');
        $payment = SupplierPayment::findOrFail($paymentId);
        $payment->delete();

        session()->flash('success', 'Payment deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $payments = SupplierPayment::with('supplier')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.supplier-payment.view', [
            'payments' => $payments,
        ])->with(['title_name' => $this->title ?? 'Supplier Payments']);
    }
}
