<?php

namespace App\Livewire\EmployeePayroll;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Payroll';
    public $perPage = 25;
    public $payrollMonth;
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
        $this->authorize('payroll.view');
        $this->employees = Employee::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['payrollMonth', 'employeeId', 'lockedFilter', 'perPage'])) {
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
        $this->authorize('payroll.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = EmployeePayroll::whereIn('id', $ids)
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
        $this->authorize('payroll.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        EmployeePayroll::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected payrolls locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('payroll.unlock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingUnlockIds = EmployeePayroll::whereIn('id', $ids)
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
        $this->authorize('payroll.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        EmployeePayroll::whereIn('id', $this->pendingUnlockIds)
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
        session()->flash('success', 'Selected payrolls unlocked.');
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->orderByDesc('payroll_month')
            ->paginate($this->perPage);

        return view('livewire.employee-payroll.view', [
            'records' => $records,
        ])->with(['title_name' => $this->title ?? 'Payroll']);
    }

    private function baseQuery()
    {
        return EmployeePayroll::with(['employee', 'lockedBy'])
            ->when($this->payrollMonth, fn ($q) => $q->where('payroll_month', $this->payrollMonth))
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
