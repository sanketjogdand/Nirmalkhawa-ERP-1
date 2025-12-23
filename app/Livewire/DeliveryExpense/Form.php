<?php

namespace App\Livewire\DeliveryExpense;

use App\Models\Dispatch;
use App\Models\DispatchDeliveryExpense;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Delivery Expenses';
    public ?int $expenseId = null;
    public $dispatch_id;
    public $supplier_id;
    public $expense_date;
    public $expense_type;
    public $amount;
    public $remarks;
    public $dispatchSearch = '';

    public $dispatchOptions = [];
    public $suppliers = [];
    public $expenseTypes = DispatchDeliveryExpense::EXPENSE_TYPES;

    public function mount($expense = null): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();

        if ($expense) {
            $record = DispatchDeliveryExpense::with('dispatch')->findOrFail($expense);
            $this->authorize('deliveryexpense.update');
            $this->expenseId = $record->id;
            $this->fill($record->only([
                'dispatch_id',
                'supplier_id',
                'expense_date',
                'expense_type',
                'amount',
                'remarks',
            ]));

            $this->expense_date = $this->expense_date ? $this->expense_date->toDateString() : null;
            $this->dispatchSearch = $record->dispatch?->dispatch_no;
            $this->dispatchOptions = Dispatch::where('id', $record->dispatch_id)->get();
        } else {
            $this->authorize('deliveryexpense.create');
            $this->dispatchOptions = Dispatch::orderByDesc('dispatch_date')->limit(20)->get();
        }
    }

    public function updated($field): void
    {
        $this->resetErrorBag($field);
        session()->forget(['success', 'error']);
    }

    public function updatedDispatchId(): void
    {
        if ($this->dispatch_id) {
            $dispatch = Dispatch::find($this->dispatch_id);
            if ($dispatch && ! $this->expense_date) {
                $this->expense_date = $dispatch->dispatch_date?->toDateString();
            }
        }
    }

    public function updatedDispatchSearch(): void
    {
        $this->loadDispatchOptions();
    }

    public function save()
    {
        $this->authorize($this->expenseId ? 'deliveryexpense.update' : 'deliveryexpense.create');
        $data = $this->validate($this->rules());

        $dispatch = Dispatch::findOrFail($data['dispatch_id']);
        if ($dispatch->is_locked) {
            session()->flash('error', 'Dispatch is locked');

            return;
        }

        $data['expense_date'] = $data['expense_date'] ?: $dispatch->dispatch_date;

        if ($this->expenseId) {
            $expense = DispatchDeliveryExpense::findOrFail($this->expenseId);
            $expense->update($data);
            session()->flash('success', 'Delivery expense updated.');
        } else {
            $data['created_by'] = auth()->id();
            DispatchDeliveryExpense::create($data);
            session()->flash('success', 'Delivery expense created.');
            $this->resetForm();
        }

        return redirect()->route('delivery-expenses.view');
    }

    public function render()
    {
        return view('livewire.delivery-expense.form')
            ->with(['title_name' => $this->title ?? 'Delivery Expenses']);
    }

    private function rules(): array
    {
        return [
            'dispatch_id' => ['required', 'exists:dispatches,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expense_date' => ['nullable', 'date'],
            'expense_type' => ['required', Rule::in(DispatchDeliveryExpense::EXPENSE_TYPES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function loadDispatchOptions(): void
    {
        $search = trim($this->dispatchSearch);

        $this->dispatchOptions = Dispatch::when($search, function ($q) use ($search) {
            $q->where('dispatch_no', 'like', '%'.$search.'%')
                ->orWhereDate('dispatch_date', $search);
        })
            ->orderByDesc('dispatch_date')
            ->limit(20)
            ->get();
    }

    private function resetForm(): void
    {
        $this->reset([
            'dispatch_id',
            'supplier_id',
            'expense_date',
            'expense_type',
            'amount',
            'remarks',
        ]);
        $this->dispatchSearch = '';
        $this->dispatchOptions = Dispatch::orderByDesc('dispatch_date')->limit(20)->get();
    }
}
