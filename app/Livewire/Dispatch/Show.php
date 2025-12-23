<?php

namespace App\Livewire\Dispatch;

use App\Models\Dispatch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Dispatch / Outward';

    public Dispatch $dispatch;

    public function mount(Dispatch $dispatch): void
    {
        $this->authorize('dispatch.view');

        $this->dispatch = $dispatch->load(
            'lines.customer',
            'lines.product',
            'lines.packSize',
            'createdBy',
            'lockedBy'
        );
    }

    public function render()
    {
        return view('livewire.dispatch.show', [
            'dispatch' => $this->dispatch,
        ])->with(['title_name' => $this->title ?? 'Dispatch / Outward']);
    }
}
