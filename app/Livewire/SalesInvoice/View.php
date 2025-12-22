<?php

namespace App\Livewire\SalesInvoice;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Sales Invoice';

    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $customerId = '';
    public $status = '';
    public $locked = '';

    public bool $showPostModal = false;
    public bool $showLockModal = false;
    public bool $showUnlockModal = false;
    public bool $showDeleteModal = false;

    public ?int $pendingPostId = null;
    public ?int $pendingLockId = null;
    public ?int $pendingUnlockId = null;
    public ?int $pendingDeleteId = null;

    public $customers;

    public function mount(): void
    {
        $this->authorize('salesinvoice.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->customers = Customer::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'customerId', 'status', 'locked'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmPost(int $id): void
    {
        $this->authorize('salesinvoice.post');
        $this->pendingPostId = $id;
        $this->showPostModal = true;
    }

    public function postConfirmed(SalesInvoiceService $invoiceService): void
    {
        $this->authorize('salesinvoice.post');

        if (! $this->pendingPostId) {
            $this->showPostModal = false;
            return;
        }

        $invoice = SalesInvoice::findOrFail($this->pendingPostId);

        try {
            $invoiceService->post($invoice);
            session()->flash('success', 'Invoice posted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showPostModal = false;
        $this->pendingPostId = null;
    }

    public function confirmLock(int $id): void
    {
        $this->authorize('salesinvoice.lock');
        $this->pendingLockId = $id;
        $this->showLockModal = true;
    }

    public function lockConfirmed(SalesInvoiceService $invoiceService): void
    {
        $this->authorize('salesinvoice.lock');

        if (! $this->pendingLockId) {
            $this->showLockModal = false;
            return;
        }

        $invoice = SalesInvoice::findOrFail($this->pendingLockId);
        $invoiceService->lock($invoice, auth()->id());
        $this->showLockModal = false;
        $this->pendingLockId = null;
        session()->flash('success', 'Invoice locked.');
    }

    public function confirmUnlock(int $id): void
    {
        $this->authorize('salesinvoice.unlock');
        $this->pendingUnlockId = $id;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(SalesInvoiceService $invoiceService): void
    {
        $this->authorize('salesinvoice.unlock');

        if (! $this->pendingUnlockId) {
            $this->showUnlockModal = false;
            return;
        }

        $invoice = SalesInvoice::findOrFail($this->pendingUnlockId);
        $invoiceService->unlock($invoice);
        $this->showUnlockModal = false;
        $this->pendingUnlockId = null;
        session()->flash('success', 'Invoice unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('salesinvoice.delete');
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(SalesInvoiceService $invoiceService): void
    {
        $this->authorize('salesinvoice.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $invoice = SalesInvoice::findOrFail($this->pendingDeleteId);

        try {
            $invoiceService->delete($invoice);
            session()->flash('success', 'Invoice deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function render()
    {
        $invoices = SalesInvoice::with('customer')
            ->when($this->fromDate, fn ($q) => $q->whereDate('invoice_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('invoice_date', '<=', $this->toDate))
            ->when($this->customerId, fn ($q) => $q->where('customer_id', $this->customerId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->locked !== '', fn ($q) => $q->where('is_locked', $this->locked ? true : false))
            ->latest('invoice_date')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.sales-invoice.view', [
            'invoices' => $invoices,
            'customers' => $this->customers,
        ])->with(['title_name' => $this->title ?? 'Sales Invoice']);
    }
}
