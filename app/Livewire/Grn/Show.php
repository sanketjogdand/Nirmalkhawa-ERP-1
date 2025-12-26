<?php

namespace App\Livewire\Grn;

use App\Models\Grn;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'GRN';
    public Grn $grn;

    public function mount(Grn $grn): void
    {
        $record = Grn::withTrashed()
            ->with(['supplier', 'purchase', 'lines.product', 'createdBy', 'lockedBy'])
            ->find($grn->id);
        $this->authorize('grn.view');
        $this->grn = $record;
    }

    public function render()
    {
        return view('livewire.grn.show', [
            'grn' => $this->grn,
        ])->with(['title_name' => $this->title ?? 'GRN']);
    }
}
