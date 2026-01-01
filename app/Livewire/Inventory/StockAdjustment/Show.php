<?php

namespace App\Livewire\Inventory\StockAdjustment;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Stock Adjustment';
    public StockAdjustment $adjustment;

    public function mount(StockAdjustment $stockAdjustment): void
    {
        $this->authorize('stockadjustment.view');

        $this->adjustment = StockAdjustment::with([
            'lines.product',
            'createdBy',
            'lockedBy',
        ])->findOrFail($stockAdjustment->id);
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment.show', [
            'adjustment' => $this->adjustment,
        ])->with(['title_name' => $this->title ?? 'Stock Adjustment']);
    }
}
