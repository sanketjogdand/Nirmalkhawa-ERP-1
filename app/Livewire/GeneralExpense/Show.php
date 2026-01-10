<?php

namespace App\Livewire\GeneralExpense;

use App\Models\GeneralExpense;
use App\Services\GeneralExpenseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'General Expense';
    public ?int $expenseId = null;

    public float $expense_total = 0;
    public float $taxable_total = 0;
    public float $gst_total = 0;
    public float $paid_total = 0;
    public float $balance = 0;

    public function mount($expense, GeneralExpenseService $service): void
    {
        $this->authorize('general_expense.view');
        $this->expenseId = (int) $expense;
        $this->refreshTotals($service);
    }

    public function render()
    {
        $expense = GeneralExpense::with([
            'supplier',
            'lines.category',
            'payments' => fn ($q) => $q->orderByDesc('payment_date')->orderByDesc('id'),
            'payments.lockedBy',
            'lockedBy',
            'createdBy',
        ])
            ->findOrFail($this->expenseId);

        return view('livewire.general-expense.show', [
            'expense' => $expense,
        ])->with(['title_name' => $this->title ?? 'General Expense']);
    }

    private function refreshTotals(GeneralExpenseService $service): void
    {
        $totals = $service->computeTotals($this->expenseId);
        $this->expense_total = $totals['expense_total'];
        $this->taxable_total = $totals['taxable_total'];
        $this->gst_total = $totals['gst_total'];
        $this->paid_total = $service->computePayments($this->expenseId);
        $this->balance = $this->expense_total - $this->paid_total;
    }
}
