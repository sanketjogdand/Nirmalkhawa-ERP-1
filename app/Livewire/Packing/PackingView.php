<?php

namespace App\Livewire\Packing;

use App\Models\Packing;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class PackingView extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Packing';
    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $productId = '';
    public $lockedFilter = '';

    public bool $canUnlock = false;
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;

    public $products = [];

    public function mount(): void
    {
        $this->authorize('packing.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->orderBy('name')
            ->get();
        $this->canUnlock = auth()->user()?->can('packing.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'productId', 'lockedFilter'])) {
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
        $this->authorize('packing.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = Packing::whereIn('id', $ids)
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
        $this->authorize('packing.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        DB::transaction(function () {
            Packing::whereIn('id', $this->pendingLockIds)
                ->where('is_locked', false)
                ->update([
                    'is_locked' => true,
                    'locked_by' => auth()->id(),
                    'locked_at' => now(),
                ]);
        });

        $this->showLockModal = false;
        $this->pendingLockIds = [];
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Selected records locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('packing.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = Packing::whereIn('id', $ids)
            ->where('is_locked', true)
            ->pluck('id')
            ->all();

        if (empty($this->pendingUnlockIds)) {
            session()->flash('danger', 'No locked records selected for unlocking.');
            return;
        }

        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('packing.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        Packing::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Packing unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('packing.delete');
        $record = Packing::findOrFail($id);

        if ($record->is_locked) {
            session()->flash('danger', 'Packing is locked. Unlock before deleting.');
            return;
        }

        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(PackingService $packingService): void
    {
        $this->authorize('packing.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $record = Packing::findOrFail($this->pendingDeleteId);

        try {
            $packingService->deletePacking($record);
            session()->flash('success', 'Packing deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->with(['product', 'createdBy', 'lockedBy'])
            ->latest('date')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.packing.packing-view', [
            'records' => $records,
            'products' => $this->products,
        ])->with(['title_name' => $this->title ?? 'Packing']);
    }

    private function baseQuery()
    {
        return Packing::query()
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->fromDate, fn ($q) => $q->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('date', '<=', $this->toDate))
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }
}
