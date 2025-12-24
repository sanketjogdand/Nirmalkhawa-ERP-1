<?php

namespace App\Livewire\CenterSettlement;

use App\Models\CenterSettlement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Center Settlement';
    public $settlement;

    public function mount($settlement)
    {
        $this->authorize('centersettlement.view');
        $record = CenterSettlement::with(['center', 'lockedBy', 'milkIntakes' => function ($q) {
            $q->orderBy('date')->orderBy('shift');
        }])->find($settlement);

        if (! $record) {
            session()->flash('danger', 'Settlement not found or has been removed.');
            return redirect()->route('center-settlements.view');
        }

        $this->settlement = $record;
    }

    public function render()
    {
        return view('livewire.center-settlement.show')
            ->with(['title_name' => $this->title ?? 'Center Settlement']);
    }
}
