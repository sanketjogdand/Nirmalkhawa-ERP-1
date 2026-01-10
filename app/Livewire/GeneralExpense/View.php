<?php

namespace App\Livewire\GeneralExpense;

use App\Models\GeneralExpense;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'General Expenses';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $supplierId;
    public $lockedFilter = '';
    public $search = '';

    public $suppliers = [];
    public bool $canUnlock = false;
    public bool $showLockModal = false;
    public ?int $pendingLockId = null;
    public bool $showUnlockModal = false;
    public ?int $pendingUnlockId = null;

    public function mount(): void
    {
        $this->authorize('general_expense.view');
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->canUnlock = auth()->user()?->can('general_expense.unlock') ?? false;
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'supplierId', 'lockedFilter', 'search'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function confirmLock(int $expenseId): void
    {
        $this->authorize('general_expense.lock');
        $expense = GeneralExpense::findOrFail($expenseId);

        if ($expense->is_locked) {
            session()->flash('danger', 'Expense already locked.');
            return;
        }

        $this->pendingLockId = $expense->id;
        $this->showLockModal = true;
    }

    public function lockConfirmed(): void
    {
        $this->authorize('general_expense.lock');

        if (! $this->pendingLockId) {
            $this->showLockModal = false;
            return;
        }

        GeneralExpense::where('id', $this->pendingLockId)
            ->where('is_locked', false)
            ->update([
                'is_locked' => true,
                'locked_by' => auth()->id(),
                'locked_at' => now(),
            ]);

        $this->pendingLockId = null;
        $this->showLockModal = false;
        session()->flash('success', 'Expense locked.');
    }

    public function confirmUnlock(int $expenseId): void
    {
        $this->authorize('general_expense.unlock');
        $expense = GeneralExpense::findOrFail($expenseId);

        if (! $expense->is_locked) {
            session()->flash('danger', 'Expense is already unlocked.');
            return;
        }

        $this->pendingUnlockId = $expense->id;
        $this->showUnlockModal = true;
    }

    public function unlockConfirmed(): void
    {
        $this->authorize('general_expense.unlock');

        if (! $this->pendingUnlockId) {
            $this->showUnlockModal = false;
            return;
        }

        GeneralExpense::where('id', $this->pendingUnlockId)
            ->where('is_locked', true)
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
            ]);

        $this->pendingUnlockId = null;
        $this->showUnlockModal = false;
        session()->flash('success', 'Expense unlocked.');
    }

    public function deleteExpense(int $expenseId): void
    {
        $this->authorize('general_expense.delete');
        $expense = GeneralExpense::findOrFail($expenseId);

        if ($expense->is_locked) {
            session()->flash('danger', 'Locked expenses cannot be deleted.');
            return;
        }

        $expense->delete();
        session()->flash('success', 'Expense deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $expenses = GeneralExpense::query()
            ->with(['supplier', 'lockedBy'])
            ->withSum('lines as expense_total', 'total_amount')
            ->withSum('payments as paid_total', 'amount')
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->lockedFilter !== '', function ($q) {
                $q->where('is_locked', $this->lockedFilter === 'locked');
            })
            ->when($this->search, function ($q) {
                $search = trim($this->search);
                $q->where(function ($sq) use ($search) {
                    $sq->where('invoice_no', 'like', '%'.$search.'%')
                        ->orWhere('remarks', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.general-expense.view', [
            'expenses' => $expenses,
        ])->with(['title_name' => $this->title ?? 'General Expenses']);
    }
}
