<?php

namespace App\Livewire\DeliveryExpense;

use App\Models\Dispatch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Manage extends Component
{
    use AuthorizesRequests;

    public Dispatch $dispatch;
    public $expenses = [];

    public function mount(int $dispatchId): void
    {
        $this->dispatch = Dispatch::with('deliveryExpenses.supplier')->findOrFail($dispatchId);
        $this->authorize('deliveryexpense.view');
        $this->refreshExpenses();
    }

    public function render()
    {
        return view('livewire.delivery-expense.manage', [
            'dispatch' => $this->dispatch,
            'expenses' => $this->expenses,
            'totalExpense' => $this->expenses->sum('amount'),
        ]);
    }

    private function refreshExpenses(): void
    {
        $this->dispatch->load('deliveryExpenses.supplier');
        $this->expenses = $this->dispatch->deliveryExpenses()->orderBy('expense_date')->get();
    }
}
