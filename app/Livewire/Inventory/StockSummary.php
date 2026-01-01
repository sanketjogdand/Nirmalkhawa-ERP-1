<?php

namespace App\Livewire\Inventory;

use App\Models\PackInventory;
use App\Models\PackSize;
use App\Models\Product;
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
    public $filter_is_packing = '';
    public $filter_can_stock = '1';
    public $filter_can_sell = '';
    public $filter_can_consume = '';
    protected array $excludedTxnTypes = [
        'DISPATCH_PACK_OUT',
        'DISPATCH_PACK_DELETED',
        'DISPATCH_PACK',
        'DISPATCH_PACK_DELETED',
    ];

    public function mount(): void
    {
        $this->authorize('inventory.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'status', 'filter_is_packing', 'filter_can_stock', 'filter_can_sell', 'filter_can_consume'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $balanceSub = DB::table('inventory_ledger_view')
            ->whereNotIn('txn_type', $this->excludedTxnTypes)
            ->selectRaw('product_id, SUM(qty_in - qty_out) as balance')
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
            ->when($this->filter_is_packing !== '', fn ($q) => $q->where('products.is_packing', (bool) ((int) $this->filter_is_packing)))
            ->when($this->filter_can_stock !== '', fn ($q) => $q->where('products.can_stock', (bool) ((int) $this->filter_can_stock)))
            ->when($this->filter_can_sell !== '', fn ($q) => $q->where('products.can_sell', (bool) ((int) $this->filter_can_sell)))
            ->when($this->filter_can_consume !== '', fn ($q) => $q->where('products.can_consume', (bool) ((int) $this->filter_can_consume)))
            ->orderBy('products.name')
            ->paginate($this->perPage);

        $productIds = $products->pluck('id');
        $packSizes = PackSize::whereIn('product_id', $productIds)->orderBy('pack_qty')->get()->groupBy('product_id');
        $packInventory = PackInventory::whereIn('product_id', $productIds)->get()->groupBy('product_id');

        return view('livewire.inventory.stock-summary', compact('products', 'packSizes', 'packInventory'))
            ->with(['title_name' => $this->title ?? 'Stock Summary']);
    }
}
