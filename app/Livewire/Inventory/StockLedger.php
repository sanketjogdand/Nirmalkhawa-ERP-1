<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
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
        $ledgers = DB::table('inventory_ledger_view as il')
            ->leftJoin('products', 'products.id', '=', 'il.product_id')
            ->select('il.*', 'products.name as product_name', 'products.code as product_code')
            ->when($this->productId, fn ($q) => $q->where('il.product_id', $this->productId))
            ->when($this->txnType, fn ($q) => $q->where('il.txn_type', $this->txnType))
            ->when($this->fromDate, fn ($q) => $q->whereDate('il.txn_datetime', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('il.txn_datetime', '<=', $this->toDate))
            ->orderByDesc('il.txn_datetime')
            ->orderByDesc('il.id')
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-ledger', [
            'ledgers' => $ledgers,
        ])->with(['title_name' => $this->title ?? 'Stock Ledger']);
    }
}
