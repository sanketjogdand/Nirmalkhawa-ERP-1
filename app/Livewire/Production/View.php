<?php

namespace App\Livewire\Production;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Services\InventoryService;
use App\Services\ProductionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Production';

    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $outputProductId = '';
    public bool $showLockModal = false;
    public bool $showUnlockModal = false;
    public ?int $pendingLockId = null;
    public ?int $pendingUnlockId = null;
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('production.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'outputProductId'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('production.delete');
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(ProductionService $productionService, InventoryService $inventoryService): void
    {
        $this->authorize('production.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $batch = ProductionBatch::findOrFail($this->pendingDeleteId);

        try {
            $productionService->delete($batch, $inventoryService);
            session()->flash('success', 'Production batch deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function confirmLock(int $id): void
    {
        $this->authorize('production.lock');
        $this->pendingLockId = $id;
        $this->showLockModal = true;
    }

    public function lockConfirmed(ProductionService $productionService): void
    {
        $this->authorize('production.lock');

        if (! $this->pendingLockId) {
            $this->showLockModal = false;
            return;
        }

        $batch = ProductionBatch::findOrFail($this->pendingLockId);
        $productionService->lock($batch, auth()->id());
        $this->showLockModal = false;
        $this->pendingLockId = null;
        session()->flash('success', 'Batch locked.');
    }

    public function confirmUnlock(int $id): void
    {
        $this->authorize('production.unlock');
        $this->pendingUnlockId = $id;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(ProductionService $productionService): void
    {
        $this->authorize('production.unlock');

        if (! $this->pendingUnlockId) {
            $this->showUnlockModal = false;
            return;
        }

        $batch = ProductionBatch::findOrFail($this->pendingUnlockId);
        $productionService->unlock($batch);
        $this->showUnlockModal = false;
        $this->pendingUnlockId = null;
        session()->flash('success', 'Batch unlocked.');
    }

    public function render()
    {
        $batches = $this->baseQuery()
            ->with(['outputProduct', 'recipe', 'createdByUser'])
            ->latest('date')
            ->latest()
            ->paginate($this->perPage);

        $products = Product::where('can_produce', true)->orderBy('name')->get();

        return view('livewire.production.view', compact('batches', 'products'))
            ->with(['title_name' => $this->title ?? 'Production']);
    }

    private function baseQuery()
    {
        $query = ProductionBatch::query();

        if ($this->fromDate) {
            $query->whereDate('date', '>=', $this->fromDate);
        }

        if ($this->toDate) {
            $query->whereDate('date', '<=', $this->toDate);
        }

        if ($this->outputProductId) {
            $query->where('output_product_id', $this->outputProductId);
        }

        return $query;
    }
}
