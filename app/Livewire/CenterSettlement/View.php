<?php

namespace App\Livewire\CenterSettlement;

use App\Models\Center;
use App\Models\CenterSettlement;
use App\Services\CenterSettlementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Center Settlements';
    public $perPage = 25;
    public $centerId = '';
    public $status = '';
    public $fromDate = '';
    public $toDate = '';

    public array $centers = [];

    public bool $showFinalizeModal = false;
    public ?int $pendingFinalizeId = null;
    public bool $showUnlockModal = false;
    public ?int $pendingUnlockId = null;
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;

    public function mount(): void
    {
        $this->authorize('centersettlement.view');
        $this->centers = Center::orderBy('name')->get()->toArray();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['centerId', 'status', 'fromDate', 'toDate', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $settlements = $this->baseQuery()
            ->latest('period_from')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.center-settlement.view', compact('settlements'))
            ->with(['title_name' => $this->title ?? 'Center Settlements']);
    }

    public function confirmFinalize(int $id): void
    {
        $this->authorize('centersettlement.finalize');
        $settlement = CenterSettlement::findOrFail($id);

        if ($settlement->status === CenterSettlement::STATUS_FINAL && $settlement->is_locked) {
            session()->flash('info', 'Settlement already finalized.');
            return;
        }

        $this->pendingFinalizeId = $id;
        $this->showFinalizeModal = true;
    }

    public function finalizeConfirmed(CenterSettlementService $service): void
    {
        $this->authorize('centersettlement.finalize');

        if (! $this->pendingFinalizeId) {
            $this->showFinalizeModal = false;
            return;
        }

        $settlement = CenterSettlement::findOrFail($this->pendingFinalizeId);
        try {
            $service->finalize($settlement, auth()->id());
            session()->flash('success', 'Settlement finalized.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->pendingFinalizeId = null;
        $this->showFinalizeModal = false;
    }

    public function confirmUnlock(int $id): void
    {
        $this->authorize('centersettlement.unlock');
        $settlement = CenterSettlement::findOrFail($id);

        if (! $settlement->is_locked) {
            session()->flash('info', 'Settlement is already unlocked.');
            return;
        }

        $this->pendingUnlockId = $id;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(CenterSettlementService $service): void
    {
        $this->authorize('centersettlement.unlock');

        if (! $this->pendingUnlockId) {
            $this->showUnlockModal = false;
            return;
        }

        $settlement = CenterSettlement::findOrFail($this->pendingUnlockId);
        $service->unlock($settlement);

        $this->pendingUnlockId = null;
        $this->showUnlockModal = false;
        session()->flash('success', 'Settlement unlocked and moved to draft.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('centersettlement.update');
        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(CenterSettlementService $service): void
    {
        $this->authorize('centersettlement.update');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $settlement = CenterSettlement::findOrFail($this->pendingDeleteId);

        try {
            $service->delete($settlement);
            session()->flash('success', 'Settlement cancelled.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->pendingDeleteId = null;
        $this->showDeleteModal = false;
    }

    private function baseQuery()
    {
        return CenterSettlement::with(['center', 'lockedBy'])
            ->when($this->centerId, fn ($q) => $q->where('center_id', $this->centerId))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->fromDate, fn ($q) => $q->where('period_from', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('period_to', '<=', $this->toDate));
    }
}
