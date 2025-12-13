<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockLedger as StockLedgerModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class StockLedger extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Stock Ledger';
    public $perPage = 25;
    public $productId = '';
    public $txnType = '';
    public $fromDate = '';
    public $toDate = '';
    public $products;

    public function mount(): void
    {
        $this->authorize('inventory.view');
        $this->products = Product::orderBy('name')->get();
        $this->fromDate = now()->subMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updating($field): void
    {
        if (in_array($field, ['productId', 'txnType', 'fromDate', 'toDate'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $ledgers = StockLedgerModel::with(['product'])
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->txnType, fn ($q) => $q->where('txn_type', $this->txnType))
            ->when($this->fromDate, fn ($q) => $q->whereDate('txn_datetime', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('txn_datetime', '<=', $this->toDate))
            ->orderByDesc('txn_datetime')
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-ledger', [
            'ledgers' => $ledgers,
        ])->with(['title_name' => $this->title ?? 'Stock Ledger']);
    }
}
