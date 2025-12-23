<?php

namespace App\Livewire\CustomerReceipt;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Customer Receipts';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $customerId;
    public ?int $confirmingDeleteId = null;

    public $customers = [];

    public function mount(): void
    {
        $this->authorize('receipt.view');
        $this->customers = Customer::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'customerId'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $receiptId): void
    {
        $this->authorize('receipt.delete');
        $this->confirmingDeleteId = $receiptId;
    }

    public function deleteReceipt(): void
    {
        if (! $this->confirmingDeleteId) {
            return;
        }

        $this->authorize('receipt.delete');
        $receipt = CustomerReceipt::findOrFail($this->confirmingDeleteId);
        $receipt->delete();

        session()->flash('success', 'Receipt deleted.');
        $this->confirmingDeleteId = null;
        $this->resetPage();
    }

    public function render()
    {
        $receipts = CustomerReceipt::with('customer')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('receipt_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('receipt_date', '<=', $this->dateTo))
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId))
            ->orderByDesc('receipt_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.customer-receipt.view', [
            'receipts' => $receipts,
        ])->with(['title_name' => $this->title ?? 'Customer Receipts']);
    }
}
