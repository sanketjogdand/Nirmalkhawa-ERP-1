<?php

namespace App\Livewire\DeliveryExpense;

use App\Models\DispatchDeliveryExpense;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Delivery Expenses';
    public $perPage = 25;
    public $dateFrom;
    public $dateTo;
    public $dispatchNo = '';
    public $supplierId;
    public $expenseType = '';
    public $message;

    public $suppliers = [];
    public $expenseTypes = DispatchDeliveryExpense::EXPENSE_TYPES;

    public function mount(): void
    {
        $this->authorize('deliveryexpense.view');
        $this->suppliers = Supplier::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['dateFrom', 'dateTo', 'dispatchNo', 'supplierId', 'expenseType'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function deleteExpense(int $expenseId): void
    {
        $this->authorize('deliveryexpense.delete');
        $expense = DispatchDeliveryExpense::with('dispatch')->findOrFail($expenseId);

        if ($expense->dispatch?->is_locked) {
            session()->flash('error', 'Dispatch is locked');

            return;
        }

        $expense->delete();
        session()->flash('success', 'Delivery expense deleted.');
        $this->resetPage();
    }

    public function render()
    {
        $query = DispatchDeliveryExpense::with(['dispatch', 'supplier'])
            ->when($this->dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->when($this->dispatchNo, function (Builder $q) {
                $q->whereHas('dispatch', fn ($dq) => $dq->where('dispatch_no', 'like', '%'.$this->dispatchNo.'%'));
            })
            ->when($this->supplierId, fn ($q) => $q->where('supplier_id', $this->supplierId))
            ->when($this->expenseType, fn ($q) => $q->where('expense_type', $this->expenseType));

        $expenses = $query->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.delivery-expense.view', [
            'expenses' => $expenses,
        ])->with(['title_name' => $this->title ?? 'Delivery Expenses']);
    }
}
