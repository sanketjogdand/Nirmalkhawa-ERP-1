<?php

namespace App\Livewire\Packing;

use App\Models\PackInventory;
use App\Models\PackSize;
use App\Models\Product;
use App\Models\StockLedger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InventorySummary extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Pack Inventory';
    public $search = '';
    public $perPage = 25;

    public function mount(): void
    {
        $this->authorize('packinventory.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $balanceSub = StockLedger::whereNotIn('txn_type', [
                StockLedger::TYPE_DISPATCH_PACK_OUT,
                StockLedger::TYPE_DISPATCH_PACK_DELETED,
                'DISPATCH_PACK', // legacy
                'DISPATCH_PACK_DELETED', // legacy
            ])
            ->selectRaw('product_id, SUM(CASE WHEN is_increase = 1 THEN qty ELSE -qty END) as balance')
            ->groupBy('product_id');

        $products = Product::query()
            ->whereHas('packSizes')
            ->leftJoinSub($balanceSub, 'sl', 'sl.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(sl.balance, 0) as bulk_stock'))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('products.name', 'like', '%'.$this->search.'%')
                        ->orWhere('products.code', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('products.name')
            ->paginate($this->perPage);

        $productIds = $products->pluck('id');
        $packSizes = PackSize::whereIn('product_id', $productIds)->orderBy('pack_qty')->get()->groupBy('product_id');
        $packInventory = PackInventory::whereIn('product_id', $productIds)->get()->keyBy('pack_size_id');

        return view('livewire.packing.inventory-summary', [
            'products' => $products,
            'packSizes' => $packSizes,
            'packInventory' => $packInventory,
        ])->with(['title_name' => $this->title ?? 'Pack Inventory']);
    }
}
