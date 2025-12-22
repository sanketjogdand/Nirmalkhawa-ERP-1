<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Suppliers';
    public Supplier $supplier;

    public function mount(Supplier $supplier): void
    {
        $this->authorize('supplier.view');
        $this->supplier = $supplier->load(['village.taluka.district.state']);
    }

    public function render()
    {
        return view('livewire.supplier.show')
            ->with(['title_name' => $this->title ?? 'Suppliers']);
    }
}
