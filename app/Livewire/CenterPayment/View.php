<?php

namespace App\Livewire\CenterPayment;

use App\Models\Center;
use App\Models\CenterPayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Center Payments';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $centerId;

    public $centers = [];
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];

    public function mount(): void
    {
        $this->authorize('centerpayment.view');
        $this->centers = Center::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'centerId'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updated($field): void
    {
        if ($field === 'selected' || str_starts_with($field, 'selected.')) {
            $this->refreshSelectionState();
        }
    }

    public function updatedSelected(): void
    {
        $this->refreshSelectionState();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selected = $this->baseQuery()->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function confirmLock(?int $id = null): void
    {
        $this->authorize('centerpayment.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = CenterPayment::whereIn('id', $ids)
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
        $this->authorize('centerpayment.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        \DB::transaction(function () {
            CenterPayment::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected payments locked.');
    }

    public function confirmUnlock(int $paymentId): void
    {
        $this->authorize('centerpayment.unlock');
        $ids = $paymentId ? [$paymentId] : $this->selected;
        $this->pendingUnlockIds = CenterPayment::whereIn('id', $ids)
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
        $this->authorize('centerpayment.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        CenterPayment::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Selected payments unlocked.');
    }

    public function deletePayment(int $paymentId): void
    {
        $this->authorize('centerpayment.delete');
        $payment = CenterPayment::findOrFail($paymentId);
        if ($payment->is_locked) {
            session()->flash('danger', 'Locked payments cannot be deleted.');
            return;
        }
        $payment->delete();

        session()->flash('success', 'Payment deleted.');
        $this->resetPage();
        $this->selectAll = false;
    }

    public function render()
    {
        $payments = $this->baseQuery()
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.center-payment.view', [
            'payments' => $payments,
        ])->with(['title_name' => $this->title ?? 'Center Payments']);
    }

    private function baseQuery()
    {
        return CenterPayment::with(['center', 'lockedBy'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->centerId, fn ($q) => $q->where('center_id', $this->centerId));
    }

    private function refreshSelectionState(): void
    {
        $this->selected = array_filter(array_unique($this->selected));
        $currentIds = $this->baseQuery()->pluck('id')->toArray();
        $this->selectAll = ! empty($currentIds) && count(array_diff($currentIds, $this->selected)) === 0;
    }
}
