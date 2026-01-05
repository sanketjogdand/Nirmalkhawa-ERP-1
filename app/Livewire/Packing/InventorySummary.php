<?php

namespace App\Livewire\Packing;

use App\Models\PackSize;
use App\Models\Product;
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
        $balanceSub = DB::table('inventory_ledger_view')
            ->selectRaw('product_id, SUM(qty_in - qty_out) as balance')
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
        $packBalances = DB::table('pack_operations_view')
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, pack_size_id, COALESCE(SUM(pack_count_in - pack_count_out), 0) as pack_balance')
            ->groupBy('product_id', 'pack_size_id')
            ->get()
            ->keyBy(fn ($row) => $row->product_id.'-'.$row->pack_size_id);

        return view('livewire.packing.inventory-summary', [
            'products' => $products,
            'packSizes' => $packSizes,
            'packBalances' => $packBalances,
        ])->with(['title_name' => $this->title ?? 'Pack Inventory']);
    }
}
