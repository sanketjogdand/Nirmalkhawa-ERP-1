<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockLedger;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class StockAdjustment extends Component
{
    use AuthorizesRequests;

    public $title = 'Stock Adjustments';
    public $product_id = '';
    public $txn_type = StockLedger::TYPE_OPENING;
    public $direction = 'IN';
    public $qty;
    public $remarks;
    public $txn_datetime;
    public $allow_negative = false;
    public $products;
    public $currentStock = 0;

    public function mount(InventoryService $inventoryService): void
    {
        $this->authorize('inventory.adjust');
        $this->products = Product::where('can_stock', true)->where('is_active', true)->orderBy('name')->get();
        $this->txn_datetime = now()->format('Y-m-d\TH:i');

        if ($this->product_id) {
            $this->currentStock = $inventoryService->getCurrentStock((int) $this->product_id);
        }
    }

    public function updatedProductId(InventoryService $inventoryService): void
    {
        $this->currentStock = $this->product_id
            ? $inventoryService->getCurrentStock((int) $this->product_id)
            : 0;
    }

    public function updatedTxnType(): void
    {
        if ($this->txn_type === StockLedger::TYPE_OPENING) {
            $this->direction = 'IN';
        }
    }

    public function save(InventoryService $inventoryService)
    {
        $this->authorize('inventory.adjust');

        $data = $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'txn_type' => ['required', Rule::in([StockLedger::TYPE_OPENING, StockLedger::TYPE_ADJ])],
            'direction' => ['required', Rule::in(['IN', 'OUT'])],
            'qty' => ['required', 'numeric', 'gt:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'txn_datetime' => ['required', 'date'],
            'allow_negative' => ['boolean'],
        ]);

        if ($data['txn_type'] === StockLedger::TYPE_OPENING) {
            $data['direction'] = 'IN';
        }

        try {
            if ($data['direction'] === 'IN') {
                $inventoryService->postIn((int) $data['product_id'], (float) $data['qty'], $data['txn_type'], [
                    'remarks' => $data['remarks'],
                    'txn_datetime' => $data['txn_datetime'],
                ]);
            } else {
                $inventoryService->postOut((int) $data['product_id'], (float) $data['qty'], $data['txn_type'], [
                    'remarks' => $data['remarks'],
                    'txn_datetime' => $data['txn_datetime'],
                ], (bool) $data['allow_negative']);
            }

            $this->currentStock = $inventoryService->getCurrentStock((int) $data['product_id']);
            $this->qty = null;
            $this->remarks = null;
            session()->flash('success', 'Stock entry saved.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment', [
            'productsList' => $this->products,
        ])->with(['title_name' => $this->title ?? 'Stock Adjustments']);
    }
}
