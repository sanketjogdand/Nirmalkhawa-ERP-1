<?php

namespace App\Livewire\Dispatch;

use App\Models\Dispatch;
use App\Services\DispatchService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Dispatch / Outward';

    public $perPage = 25;
    public $fromDate = '';
    public $toDate = '';
    public $status = '';
    public $deliveryMode = '';

    public bool $showPostModal = false;
    public bool $showLockModal = false;
    public bool $showUnlockModal = false;
    public bool $showDeleteModal = false;

    public ?int $pendingPostId = null;
    public ?int $pendingLockId = null;
    public ?int $pendingUnlockId = null;
    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('dispatch.view');
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['fromDate', 'toDate', 'status', 'deliveryMode'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmPost(int $id): void
    {
        $this->authorize('dispatch.post');
        $this->pendingPostId = $id;
        $this->showPostModal = true;
    }

    public function postConfirmed(DispatchService $dispatchService, InventoryService $inventoryService): void
    {
        $this->authorize('dispatch.post');

        if (! $this->pendingPostId) {
            $this->showPostModal = false;
            return;
        }

        $dispatch = Dispatch::findOrFail($this->pendingPostId);

        try {
            $dispatchService->post($dispatch, $inventoryService);
            session()->flash('success', 'Dispatch posted and stock updated.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showPostModal = false;
        $this->pendingPostId = null;
    }

    public function confirmLock(int $id): void
    {
        $this->authorize('dispatch.lock');
        $this->pendingLockId = $id;
        $this->showLockModal = true;
    }

    public function lockConfirmed(DispatchService $dispatchService): void
    {
        $this->authorize('dispatch.lock');

        if (! $this->pendingLockId) {
            $this->showLockModal = false;
            return;
        }

        $dispatch = Dispatch::findOrFail($this->pendingLockId);
        $dispatchService->lock($dispatch, auth()->id());
        $this->showLockModal = false;
        $this->pendingLockId = null;
        session()->flash('success', 'Dispatch locked.');
    }

    public function confirmUnlock(int $id): void
    {
        $this->authorize('dispatch.unlock');
        $this->pendingUnlockId = $id;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(DispatchService $dispatchService): void
    {
        $this->authorize('dispatch.unlock');

        if (! $this->pendingUnlockId) {
            $this->showUnlockModal = false;
            return;
        }

        $dispatch = Dispatch::findOrFail($this->pendingUnlockId);
        $dispatchService->unlock($dispatch);
        $this->showUnlockModal = false;
        $this->pendingUnlockId = null;
        session()->flash('success', 'Dispatch unlocked.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('dispatch.delete');
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(DispatchService $dispatchService, InventoryService $inventoryService): void
    {
        $this->authorize('dispatch.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $dispatch = Dispatch::findOrFail($this->pendingDeleteId);

        try {
            $dispatchService->delete($dispatch, $inventoryService);
            session()->flash('success', 'Dispatch deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->pendingDeleteId = null;
    }

    public function render()
    {
        $dispatches = Dispatch::withCount('lines')
            ->with(['createdBy'])
            ->when($this->fromDate, fn ($q) => $q->whereDate('dispatch_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('dispatch_date', '<=', $this->toDate))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->deliveryMode, fn ($q) => $q->where('delivery_mode', $this->deliveryMode))
            ->latest('dispatch_date')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.dispatch.view', ['dispatches' => $dispatches])
            ->with(['title_name' => $this->title ?? 'Dispatch / Outward']);
    }
}
