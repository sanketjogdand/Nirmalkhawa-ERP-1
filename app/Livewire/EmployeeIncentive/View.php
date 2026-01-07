<?php

namespace App\Livewire\EmployeeIncentive;

use App\Models\Employee;
use App\Models\EmployeeIncentive;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Incentives';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $employeeId;
    public $lockedFilter = '';

    public $employees = [];
    public array $selected = [];
    public bool $selectAll = false;
    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];

    public function mount(): void
    {
        $this->authorize('incentive.view');
        $this->employees = Employee::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'employeeId', 'lockedFilter', 'perPage'])) {
            $this->resetPage();
            $this->selected = [];
            $this->selectAll = false;
        }
    }

    public function updated($field): void
    {
        if ($field === 'selected' || str_starts_with($field, 'selected.')) {
            $this->refreshSelectionState();
        }
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
        $this->authorize('incentive.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = EmployeeIncentive::whereIn('id', $ids)
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
        $this->authorize('incentive.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        EmployeeIncentive::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected incentives locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('incentive.unlock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingUnlockIds = EmployeeIncentive::whereIn('id', $ids)
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
        $this->authorize('incentive.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        EmployeeIncentive::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Selected incentives unlocked.');
    }

    public function delete(int $incentiveId): void
    {
        $this->authorize('incentive.delete');
        $record = EmployeeIncentive::findOrFail($incentiveId);
        if ($record->is_locked) {
            session()->flash('danger', 'Locked incentives cannot be deleted.');
            return;
        }
        $record->delete();
        session()->flash('success', 'Incentive deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->orderByDesc('incentive_date')
            ->paginate($this->perPage);

        return view('livewire.employee-incentive.view', [
            'records' => $records,
        ])->with(['title_name' => $this->title ?? 'Incentives']);
    }

    private function baseQuery()
    {
        return EmployeeIncentive::with(['employee', 'lockedBy'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('incentive_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('incentive_date', '<=', $this->dateTo))
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->when($this->lockedFilter !== '', fn ($q) => $q->where('is_locked', $this->lockedFilter ? true : false));
    }

    private function refreshSelectionState(): void
    {
        $this->selected = array_filter(array_unique($this->selected));
        $currentIds = $this->baseQuery()->pluck('id')->toArray();
        $this->selectAll = ! empty($currentIds) && count(array_diff($currentIds, $this->selected)) === 0;
    }
}
