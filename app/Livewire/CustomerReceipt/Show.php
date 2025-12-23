<?php

namespace App\Livewire\CustomerReceipt;

use App\Models\CustomerReceipt;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Customer Receipts';
    public CustomerReceipt $receipt;

    public function mount(CustomerReceipt $receipt): void
    {
        $this->authorize('receipt.view');
        $this->receipt = $receipt->load(['customer', 'createdBy']);
    }

    public function render()
    {
        return view('livewire.customer-receipt.show')
            ->with(['title_name' => $this->title ?? 'Customer Receipts']);
    }
}
