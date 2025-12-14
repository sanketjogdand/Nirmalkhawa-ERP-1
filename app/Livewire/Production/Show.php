<?php

namespace App\Livewire\Production;

use App\Models\ProductionBatch;
use App\Models\StockLedger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Production';

    public ProductionBatch $batch;
    public $ledgers;

    public function mount($production): void
    {
        $this->authorize('production.view');
        $this->batch = ProductionBatch::with([
            'outputProduct',
            'recipe',
            'inputs.materialProduct',
            'yieldBaseProduct',
            'lockedByUser',
            'createdByUser',
        ])->findOrFail($production);

        $this->ledgers = StockLedger::with('product')
            ->where('reference_type', ProductionBatch::class)
            ->where('reference_id', $this->batch->id)
            ->orderBy('txn_datetime')
            ->get();
    }

    public function render()
    {
        return view('livewire.production.show', ['batch' => $this->batch, 'ledgers' => $this->ledgers])
            ->with(['title_name' => $this->title ?? 'Production']);
    }
}
