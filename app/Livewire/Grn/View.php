<?php

namespace App\Livewire\Grn;

use App\Models\Grn;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\GrnService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Material Received (GRN)';
    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $supplierId = '';
    public $productId = '';
    public $lockedFilter = '';
    public $purchaseLinked = '';
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
    public $products = [];

    public function mount(): void
    {
        $this->authorize('grn.view');
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->products = Product::where('can_purchase', true)->where('can_stock', true)->orderBy('name')->get();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->canUnlock = auth()->user()?->can('grn.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'supplierId', 'productId', 'lockedFilter', 'purchaseLinked'])) {
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
        $this->authorize('grn.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = Grn::whereIn('id', $ids)
            ->where('is_locked', false)
            ->pluck('id')
            ->all();

        if (empty($this->pendingLockIds)) {
            session()->flash('danger', 'No unlocked GRNs selected for locking.');
            return;
        }

        $this->showLockModal = true;
    }

    public function lockConfirmed(): void
    {
        $this->authorize('grn.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        Grn::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected GRNs locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('grn.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = Grn::whereIn('id', $ids)
            ->where('is_locked', true)
            ->pluck('id')
            ->all();

        if (empty($this->pendingUnlockIds)) {
            session()->flash('danger', 'No locked GRNs selected for unlocking.');
            return;
        }

        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('grn.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        Grn::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'GRN(s) unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('grn.delete');
        $grn = Grn::findOrFail($id);

        if ($grn->is_locked) {
            session()->flash('danger', 'GRN is locked. Ask admin to unlock first.');
            return;
        }

        $this->pendingDeleteId = $grn->id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(GrnService $grnService, InventoryService $inventoryService): void
    {
        $this->authorize('grn.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $grn = Grn::findOrFail($this->pendingDeleteId);

        if ($grn->is_locked) {
            session()->flash('danger', 'GRN is locked. Ask admin to unlock first.');
            $this->showDeleteModal = false;
            $this->pendingDeleteId = null;
            return;
        }

        $grnService->delete($grn, $inventoryService);
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'GRN deleted.');
    }

    public function render()
    {
        $grns = $this->baseQuery()
            ->withSum('lines as total_received_qty', 'received_qty')
            ->orderByDesc('grn_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.grn.view', [
            'grns' => $grns,
        ])->with(['title_name' => $this->title ?? 'Material Received (GRN)']);
    }

    private function baseQuery()
    {
        return Grn::with(['supplier', 'purchase', 'lockedBy'])
            ->when($this->fromDate, fn ($q) => $q->whereDate('grn_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('grn_date', '<=', $this->toDate))
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->productId, function ($q) {
                $q->whereHas('lines', function ($sq) {
                    $sq->where('product_id', $this->productId);
                });
            })
            ->when($this->purchaseLinked === 'yes', fn ($q) => $q->whereNotNull('purchase_id'))
            ->when($this->purchaseLinked === 'no', fn ($q) => $q->whereNull('purchase_id'))
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }
}
