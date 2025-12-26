<?php

namespace App\Livewire\MaterialConsumption;

use App\Models\MaterialConsumption;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Material Consumption';
    public MaterialConsumption $consumption;

    public function mount(MaterialConsumption $materialConsumption): void
    {
        $record = MaterialConsumption::withTrashed()
            ->with(['lines.product', 'createdBy', 'lockedBy'])
            ->findOrFail($materialConsumption->id);
        $this->authorize('materialconsumption.view');
        $this->consumption = $record;
    }

    public function render()
    {
        return view('livewire.material-consumption.show', [
            'consumption' => $this->consumption,
        ])->with(['title_name' => $this->title ?? 'Material Consumption']);
    }
}
