<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockSummary extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Stock Summary';
    public $perPage = 25;
    public $search = '';
    public $status = '';
    public $filter_can_stock = '1';
    public $filter_can_sell = '';
    public $filter_can_consume = '';

    public function mount(): void
    {
        $this->authorize('inventory.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'status', 'filter_can_stock', 'filter_can_sell', 'filter_can_consume'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $balanceSub = StockLedger::selectRaw('product_id, SUM(CASE WHEN is_increase = 1 THEN qty ELSE -qty END) as balance')
            ->groupBy('product_id');

        $products = Product::query()
            ->leftJoinSub($balanceSub, 'sl', 'sl.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(sl.balance, 0) as stock_balance'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('products.name', 'like', '%'.$this->search.'%')
                        ->orWhere('products.code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('products.is_active', (bool) ((int) $this->status)))
            ->when($this->filter_can_stock !== '', fn ($q) => $q->where('products.can_stock', (bool) ((int) $this->filter_can_stock)))
            ->when($this->filter_can_sell !== '', fn ($q) => $q->where('products.can_sell', (bool) ((int) $this->filter_can_sell)))
            ->when($this->filter_can_consume !== '', fn ($q) => $q->where('products.can_consume', (bool) ((int) $this->filter_can_consume)))
            ->orderBy('products.name')
            ->paginate($this->perPage);

        return view('livewire.inventory.stock-summary', compact('products'))
            ->with(['title_name' => $this->title ?? 'Stock Summary']);
    }
}
