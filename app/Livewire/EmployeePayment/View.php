<?php

namespace App\Livewire\EmployeePayment;

use App\Models\Employee;
use App\Models\EmployeePayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Employee Payments';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $employeeId;
    public $paymentType;
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
        $this->authorize('employee_payment.view');
        $this->employees = Employee::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'employeeId', 'paymentType', 'lockedFilter', 'perPage'])) {
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
        $this->authorize('employee_payment.lock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingLockIds = EmployeePayment::whereIn('id', $ids)
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
        $this->authorize('employee_payment.lock');

        if (empty($this->pendingLockIds)) {
            $this->showLockModal = false;
            return;
        }

        EmployeePayment::whereIn('id', $this->pendingLockIds)
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
        session()->flash('success', 'Selected payments locked.');
    }

    public function confirmUnlock(?int $id = null): void
    {
        $this->authorize('employee_payment.unlock');
        $ids = $id ? [$id] : $this->selected;
        $this->pendingUnlockIds = EmployeePayment::whereIn('id', $ids)
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
        $this->authorize('employee_payment.unlock');

        if (empty($this->pendingUnlockIds)) {
            $this->showUnlockModal = false;
            return;
        }

        EmployeePayment::whereIn('id', $this->pendingUnlockIds)
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

    public function delete(int $paymentId): void
    {
        $this->authorize('employee_payment.delete');
        $record = EmployeePayment::findOrFail($paymentId);
        if ($record->is_locked) {
            session()->flash('danger', 'Locked payments cannot be deleted.');
            return;
        }
        $record->delete();
        session()->flash('success', 'Payment deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $records = $this->baseQuery()
            ->orderByDesc('payment_date')
            ->paginate($this->perPage);

        return view('livewire.employee-payment.view', [
            'records' => $records,
        ])->with(['title_name' => $this->title ?? 'Employee Payments']);
    }

    private function baseQuery()
    {
        return EmployeePayment::with(['employee', 'lockedBy'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $this->dateTo))
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->when($this->paymentType, fn ($q) => $q->where('payment_type', $this->paymentType))
            ->when($this->lockedFilter !== '', fn ($q) => $q->where('is_locked', $this->lockedFilter ? true : false));
    }

    private function refreshSelectionState(): void
    {
        $this->selected = array_filter(array_unique($this->selected));
        $currentIds = $this->baseQuery()->pluck('id')->toArray();
        $this->selectAll = ! empty($currentIds) && count(array_diff($currentIds, $this->selected)) === 0;
    }
}
