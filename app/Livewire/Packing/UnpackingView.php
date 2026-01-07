<?php

namespace App\Livewire\Packing;

use App\Models\Product;
use App\Models\Unpacking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class UnpackingView extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Unpacking';
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
        $this->authorize('unpacking.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->orderBy('name')
            ->get();
        $this->canUnlock = auth()->user()?->can('unpacking.unlock') ?? false;
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
        $this->authorize('unpacking.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = Unpacking::whereIn('id', $ids)
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
        $this->authorize('unpacking.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        DB::transaction(function () {
            Unpacking::whereIn('id', $this->pendingLockIds)
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
        $this->authorize('unpacking.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = Unpacking::whereIn('id', $ids)
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
        $this->authorize('unpacking.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        Unpacking::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Unpacking unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('unpacking.delete');
        $record = Unpacking::findOrFail($id);

        if ($record->is_locked) {
            session()->flash('danger', 'Unpacking is locked. Unlock before deleting.');
            return;
        }

        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(PackingService $packingService): void
    {
        $this->authorize('unpacking.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $record = Unpacking::findOrFail($this->pendingDeleteId);

        try {
            $packingService->deleteUnpacking($record);
            session()->flash('success', 'Unpacking deleted.');
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

        return view('livewire.packing.unpacking-view', [
            'records' => $records,
            'products' => $this->products,
        ])->with(['title_name' => $this->title ?? 'Unpacking']);
    }

    private function baseQuery()
    {
        return Unpacking::query()
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->fromDate, fn ($q) => $q->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('date', '<=', $this->toDate))
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }
}
