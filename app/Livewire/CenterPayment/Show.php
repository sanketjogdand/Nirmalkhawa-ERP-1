<?php

namespace App\Livewire\CenterPayment;

use App\Models\CenterPayment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Center Payments';
    public CenterPayment $payment;

    public function mount(CenterPayment $payment): void
    {
        $this->authorize('centerpayment.view');
        $this->payment = $payment->load(['center', 'createdBy', 'lockedBy']);
    }

    public function render()
    {
        return view('livewire.center-payment.show')
            ->with(['title_name' => $this->title ?? 'Center Payments']);
    }
}
