<?php

namespace App\Livewire\Purchase;

use App\Models\Purchase;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Purchase';
    public Purchase $purchase;

    public function mount(Purchase $purchase): void
    {
        $this->authorize('purchase.view');
        $this->purchase = $purchase->load(['supplier', 'lines.product', 'lockedBy', 'createdBy']);
    }

    public function render()
    {
        return view('livewire.purchase.show')->with(['title_name' => $this->title ?? 'Purchase']);
    }
}
