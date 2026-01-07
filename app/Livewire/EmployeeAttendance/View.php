<?php

namespace App\Livewire\EmployeeAttendance;

use App\Models\EmployeeAttendanceHeader;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Attendance';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $lockedFilter = '';

    public array $selected = [];
    public bool $selectAll = false;
    public bool $showLockModal = false;
    public array $pendingLockIds = [];
    public bool $showUnlockModal = false;
    public array $pendingUnlockIds = [];

    public function mount(): void
    {
        $this->authorize('attendance.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'lockedFilter', 'perPage'])) {
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
        $this->authorize('attendance.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = EmployeeAttendanceHeader::whereIn('id', $ids)
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
        $this->authorize('attendance.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        EmployeeAttendanceHeader::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected attendance sheets locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('attendance.unlock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingUnlockIds = EmployeeAttendanceHeader::whereIn('id', $ids)
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
        $this->authorize('attendance.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        EmployeeAttendanceHeader::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Selected attendance sheets unlocked.');
    }

    public function delete(int $attendanceId): void
    {
        $this->authorize('attendance.delete');
        $record = EmployeeAttendanceHeader::findOrFail($attendanceId);
        if ($record->is_locked) {
            session()->flash('danger', 'Locked attendance cannot be deleted.');
            return;
        }
        $record->delete();
        session()->flash('success', 'Attendance deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->orderByDesc('attendance_date')
            ->paginate($this->perPage);

        return view('livewire.employee-attendance.view', [
            'records' => $records,
        ])->with(['title_name' => $this->title ?? 'Attendance']);
    }

    private function baseQuery()
    {
        return EmployeeAttendanceHeader::with(['lockedBy'])
            ->withCount('lines')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('attendance_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('attendance_date', '<=', $this->dateTo))
            ->when($this->lockedFilter !== '', fn ($q) => $q->where('is_locked', $this->lockedFilter ? true : false));
    }

    private function refreshSelectionState(): void
    {
        $this->selected = array_filter(array_unique($this->selected));
        $currentIds = $this->baseQuery()->pluck('id')->toArray();
        $this->selectAll = ! empty($currentIds) && count(array_diff($currentIds, $this->selected)) === 0;
    }
}
