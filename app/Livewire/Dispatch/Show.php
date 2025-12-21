<?php

namespace App\Livewire\Dispatch;

use App\Models\Dispatch;
use App\Models\DispatchLine;
use App\Models\StockLedger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Dispatch / Outward';

    public Dispatch $dispatch;
    public $ledgers;

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

        $lineIds = $this->dispatch->lines->pluck('id');

        $this->ledgers = StockLedger::with('product')
            ->where('reference_type', DispatchLine::class)
            ->whereIn('reference_id', $lineIds)
            ->orderBy('txn_datetime')
            ->get();
    }

    public function render()
    {
        return view('livewire.dispatch.show', [
            'dispatch' => $this->dispatch,
            'ledgers' => $this->ledgers,
        ])->with(['title_name' => $this->title ?? 'Dispatch / Outward']);
    }
}
