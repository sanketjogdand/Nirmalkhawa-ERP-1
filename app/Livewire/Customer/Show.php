<?php

namespace App\Livewire\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Customers';
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        $this->authorize('customer.view');
        $this->customer = $customer->load(['village.taluka.district.state']);
    }

    public function render()
    {
        return view('livewire.customer.show')
            ->with(['title_name' => $this->title ?? 'Customers']);
    }
}
