<?php

namespace App\Livewire\SupplierPayment;

use App\Models\DispatchDeliveryExpense;
use App\Models\SupplierPayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Supplier Payments';
    public SupplierPayment $payment;

    public function mount(SupplierPayment $payment): void
    {
        $this->authorize('supplierpayment.view');
        $this->payment = $payment->load(['supplier', 'createdBy']);
    }

    public function render()
    {
        return view('livewire.supplier-payment.show')
            ->with(['title_name' => $this->title ?? 'Supplier Payments']);
    }
}
