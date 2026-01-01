<?php

namespace App\Livewire\Inventory\StockAdjustment;

use App\Models\StockAdjustment;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Stock Adjustments';
    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $reason = '';
    public $lockedFilter = '';
    public bool $canUnlock = false;

    public array $reasons = [];
    public array $selected = [];
    public bool $selectAll = false;
    public array $pendingLockIds = [];
    public array $pendingUnlockIds = [];
    public bool $showLockModal = false;
    public bool $showUnlockModal = false;
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('stockadjustment.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->reasons = StockAdjustment::REASONS;
        $this->canUnlock = auth()->user()?->can('stockadjustment.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'reason', 'lockedFilter'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
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

    public function confirmLock(?int $id = null): void
    {
        $this->authorize('stockadjustment.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = StockAdjustment::whereIn('id', $ids)
            ->where('is_locked', false)
            ->pluck('id')
            ->all();

        if (empty($this->pendingLockIds)) {
            session()->flash('danger', 'No unlocked adjustments selected for locking.');
            return;
        }

        $this->showLockModal = true;
    }

    public function lockConfirmed(): void
    {
        $this->authorize('stockadjustment.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        StockAdjustment::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected adjustments locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('stockadjustment.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = StockAdjustment::whereIn('id', $ids)
            ->where('is_locked', true)
            ->pluck('id')
            ->all();

        if (empty($this->pendingUnlockIds)) {
            session()->flash('danger', 'No locked adjustments selected for unlocking.');
            return;
        }

        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('stockadjustment.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        StockAdjustment::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Adjustment(s) unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('stockadjustment.delete');
        $adjustment = StockAdjustment::findOrFail($id);

        if ($adjustment->is_locked) {
            session()->flash('danger', 'Locked adjustments cannot be deleted.');
            return;
        }

        $this->pendingDeleteId = $adjustment->id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(StockAdjustmentService $service): void
    {
        $this->authorize('stockadjustment.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $adjustment = StockAdjustment::findOrFail($this->pendingDeleteId);

        if ($adjustment->is_locked) {
            session()->flash('danger', 'Locked adjustments cannot be deleted.');
            $this->resetDeleteState();
            return;
        }

        try {
            $service->delete($adjustment);
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
            $this->resetDeleteState();
            return;
        }

        $this->resetDeleteState();
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
        session()->flash('success', 'Adjustment deleted.');
    }

    public function render()
    {
        $adjustments = $this->baseQuery()
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-adjustment.view', [
            'adjustments' => $adjustments,
        ])->with(['title_name' => $this->title ?? 'Stock Adjustments']);
    }

    private function baseQuery()
    {
        return StockAdjustment::with(['lockedBy'])
            ->withCount('lines')
            ->when($this->fromDate, fn ($q) => $q->whereDate('adjustment_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('adjustment_date', '<=', $this->toDate))
            ->when($this->reason, fn ($q) => $q->where('reason', $this->reason))
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }

    private function resetDeleteState(): void
    {
        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }
}
