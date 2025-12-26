<?php

namespace App\Livewire\MaterialConsumption;

use App\Models\MaterialConsumption;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MaterialConsumptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Material Consumption';

    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $consumptionType = '';
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

    public array $consumptionTypes = [];
    public $products = [];

    public function mount(): void
    {
        $this->authorize('materialconsumption.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->consumptionTypes = config('material_consumption.types', []);
        $this->products = Product::where('can_consume', true)
            ->where('can_stock', true)
            ->orderBy('name')
            ->get();
        $this->canUnlock = auth()->user()?->can('materialconsumption.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'consumptionType', 'productId', 'lockedFilter'])) {
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
        $this->authorize('materialconsumption.lock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingLockIds = MaterialConsumption::whereIn('id', $ids)
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
        $this->authorize('materialconsumption.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        MaterialConsumption::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected records locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('materialconsumption.unlock');
        $ids = $id ? [$id] : $this->selected;

        $this->pendingUnlockIds = MaterialConsumption::whereIn('id', $ids)
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
        $this->authorize('materialconsumption.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        MaterialConsumption::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Records unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('materialconsumption.delete');
        $record = MaterialConsumption::findOrFail($id);

        if ($record->is_locked) {
            session()->flash('danger', 'Record is locked. Unlock before deleting.');
            return;
        }

        $this->pendingDeleteId = $record->id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(MaterialConsumptionService $service, InventoryService $inventoryService): void
    {
        $this->authorize('materialconsumption.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $record = MaterialConsumption::findOrFail($this->pendingDeleteId);

        if ($record->is_locked) {
            session()->flash('danger', 'Record is locked. Unlock before deleting.');
            $this->showDeleteModal = false;
            $this->pendingDeleteId = null;
            return;
        }

        try {
            $service->delete($record, $inventoryService);
            session()->flash('success', 'Record deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->with(['lockedBy'])
            ->withSum('lines as total_qty', 'qty')
            ->orderByDesc('consumption_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.material-consumption.view', [
            'records' => $records,
            'consumptionTypes' => $this->consumptionTypes,
        ])->with(['title_name' => $this->title ?? 'Material Consumption']);
    }

    private function baseQuery()
    {
        return MaterialConsumption::query()
            ->when($this->fromDate, fn ($q) => $q->whereDate('consumption_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('consumption_date', '<=', $this->toDate))
            ->when($this->consumptionType, fn ($q) => $q->where('consumption_type', $this->consumptionType))
            ->when($this->productId, function ($q) {
                $q->whereHas('lines', function ($sq) {
                    $sq->where('product_id', $this->productId);
                });
            })
            ->when($this->lockedFilter === 'locked', fn ($q) => $q->where('is_locked', true))
            ->when($this->lockedFilter === 'unlocked', fn ($q) => $q->where('is_locked', false));
    }
}
