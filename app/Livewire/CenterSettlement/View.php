<?php

namespace App\Livewire\CenterSettlement;

use App\Models\Center;
use App\Models\CenterSettlement;
use App\Services\CenterSettlementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
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
    public $fromDate = '';
    public $toDate = '';

    public array $centers = [];

    public bool $showLockModal = false;
    public ?int $pendingLockId = null;
    public bool $showUnlockModal = false;
    public ?int $pendingUnlockId = null;
    public bool $showDeleteModal = false;
    public ?int $pendingDeleteId = null;
    public string $pendingSummary = '';

    public function mount(): void
    {
        $this->authorize('centersettlement.view');
        $this->centers = Center::orderBy('name')->get()->toArray();
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['centerId', 'fromDate', 'toDate', 'perPage'])) {
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

    public function confirmLock(int $id): void
    {
        $this->authorize('centersettlement.lock');
        $settlement = CenterSettlement::findOrFail($id);

        if ($settlement->is_locked) {
            session()->flash('info', 'Settlement already locked.');
            return;
        }

        $this->pendingLockId = $id;
        $this->showLockModal = true;
        $this->pendingSummary = $this->summarize($settlement);
    }

    public function lockConfirmed(CenterSettlementService $service): void
    {
        $this->authorize('centersettlement.lock');

        if (! $this->pendingLockId) {
            $this->showLockModal = false;
            return;
        }

        $settlement = CenterSettlement::findOrFail($this->pendingLockId);
        try {
            $service->lock($settlement, auth()->id());
            session()->flash('success', 'Settlement locked.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->pendingLockId = null;
        $this->showLockModal = false;
        $this->pendingSummary = '';
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
        $this->pendingSummary = $this->summarize($settlement);
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
        session()->flash('success', 'Settlement unlocked.');
        $this->pendingSummary = '';
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('centersettlement.delete');
        $settlement = CenterSettlement::findOrFail($id);

        if ($settlement->is_locked) {
            session()->flash('danger', 'Settlement is locked. Ask admin to unlock first.');
            return;
        }

        $this->pendingDeleteId = $id;
        $this->showDeleteModal = true;
        $this->pendingSummary = $this->summarize($settlement);
    }

    public function deleteConfirmed(CenterSettlementService $service): void
    {
        $this->authorize('centersettlement.delete');

        if (! $this->pendingDeleteId) {
            $this->showDeleteModal = false;
            return;
        }

        $settlement = CenterSettlement::findOrFail($this->pendingDeleteId);

        try {
            $service->delete($settlement);
            session()->flash('success', 'Settlement deleted.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }

        $this->pendingDeleteId = null;
        $this->showDeleteModal = false;
        $this->pendingSummary = '';
    }

    private function baseQuery()
    {
        return CenterSettlement::with(['center', 'lockedBy'])
            ->when($this->centerId, fn ($q) => $q->where('center_id', $this->centerId))
            ->when($this->fromDate, fn ($q) => $q->where('period_from', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->where('period_to', '<=', $this->toDate));
    }

    private function summarize(CenterSettlement $settlement): string
    {
        $from = $settlement->period_from ? Carbon::parse($settlement->period_from)->format('d M Y') : '';
        $to = $settlement->period_to ? Carbon::parse($settlement->period_to)->format('d M Y') : '';
        $center = $settlement->center?->name;

        return trim("{$center} ({$from} to {$to})");
    }
}
