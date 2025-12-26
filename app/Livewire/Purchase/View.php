<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Purchases';
    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $supplierId = '';
    public $lockedFilter = '';
    public $billNo = '';
    public bool $canUnlock = false;

    public array $selected = [];
    public bool $selectAll = false;
    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;

    public $suppliers = [];

    public function mount(): void
    {
        $this->authorize('purchase.view');
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->canUnlock = auth()->user()?->can('purchase.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'supplierId', 'lockedFilter', 'billNo'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->baseQuery()
                ->when(! $this->canUnlock, fn ($q) => $q->where('is_locked', false))
                ->pluck('id')
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updatedSelected(): void
    {
        $currentIds = $this->baseQuery()
            ->when(! $this->canUnlock, fn ($q) => $q->where('is_locked', false))
            ->pluck('id')
            ->toArray();
        if (empty($currentIds)) {
            $this->selectAll = false;
            return;
        }

        $this->selectAll = count(array_diff($currentIds, $this->selected)) === 0;
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmLock(?int $id = null): void
    {
        $this->authorize('purchase.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = Purchase::whereIn('id', $ids)
            ->where('is_locked', false)
            ->pluck('id')
            ->all();

        if (empty($this->pendingLockIds)) {
            session()->flash('danger', 'No unlocked records selected for locking.');
            return;
        }

        $this->showLockModal = true;
    }

    public function lockConfirmed(): void
    {
        $this->authorize('purchase.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        Purchase::whereIn('id', $this->pendingLockIds)
            ->where('is_locked', false)
            ->update([
                'is_locked' => true,
                'locked_by' => auth()->id(),
                'locked_at' => now(),
            ]);

        $this->showLockModal = false;
        $this->pendingLockIds = [];
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected purchases locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('purchase.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = Purchase::whereIn('id', $ids)
            ->where('is_locked', true)
            ->pluck('id')
            ->all();

        if (empty($this->pendingUnlockIds)) {
            session()->flash('danger', 'No locked purchases selected for unlocking.');
            return;
        }

        $this->pendingUnlockId = null;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('purchase.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        Purchase::whereIn('id', $this->pendingUnlockIds)
            ->where('is_locked', true)
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
            ]);

        $this->showUnlockModal = false;
        $this->pendingUnlockIds = [];
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Purchase(s) unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('purchase.delete');
        $purchase = Purchase::findOrFail($id);

        if ($purchase->is_locked) {
            session()->flash('danger', 'Locked purchases cannot be deleted.');
            return;
        }

        $this->pendingDeleteId = $purchase->id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('purchase.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $purchase = Purchase::findOrFail($this->pendingDeleteId);

        if ($purchase->is_locked) {
            session()->flash('danger', 'Locked purchases cannot be deleted.');
            $this->showDeleteModal = false;
            $this->pendingDeleteId = null;
            return;
        }

        $purchase->delete();
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Purchase deleted.');
    }

    public function render()
    {
        $purchases = $this->baseQuery()
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.purchase.view', [
            'purchases' => $purchases,
        ])->with(['title_name' => $this->title ?? 'Purchases']);
    }

    private function baseQuery()
    {
        return Purchase::with(['supplier', 'lockedBy'])
            ->when($this->fromDate, fn ($q) => $q->whereDate('purchase_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('purchase_date', '<=', $this->toDate))
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->billNo, fn ($q) => $q->where('supplier_bill_no', 'like', '%'.$this->billNo.'%'))
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }
}
