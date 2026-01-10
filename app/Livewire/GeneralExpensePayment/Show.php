<?php

namespace App\Livewire\GeneralExpensePayment;

use App\Models\GeneralExpensePayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'General Expenses Payments';
    public GeneralExpensePayment $payment;

    public function mount(GeneralExpensePayment $payment): void
    {
        $this->authorize('general_expense_payment.view');
        $this->payment = $payment->load(['supplier', 'createdBy', 'lockedBy']);
    }

    public function render()
    {
        return view('livewire.general-expense-payment.show')
            ->with(['title_name' => $this->title ?? 'General Expenses Payments']);
    }
}
