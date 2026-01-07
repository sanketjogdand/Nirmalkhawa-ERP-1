<?php

namespace App\Livewire\Packing;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Packing / Unpacking History';
    public $perPage = 25;
    public $productId = '';
    public $operationType = '';
    public $fromDate = '';
    public $toDate = '';
    public $products;

    public function mount(): void
    {
        $this->authorize('packinventory.view');
        $this->fromDate = now()->subMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->products = \App\Models\Product::orderBy('name')->get();
    }

    public function updating($field): void
    {
        if (in_array($field, ['productId', 'operationType', 'fromDate', 'toDate'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $packingQuery = DB::table('packing_items')
            ->join('packings', 'packings.id', '=', 'packing_items.packing_id')
            ->join('products', 'products.id', '=', 'packings.product_id')
            ->selectRaw("
                packings.date as date,
                packings.product_id,
                products.name as product_name,
                products.code as product_code,
                'PACK' as operation,
                packing_items.pack_size_id,
                packing_items.pack_qty_snapshot,
                packing_items.pack_uom,
                packing_items.pack_count,
                (packing_items.pack_qty_snapshot * packing_items.pack_count) as bulk_qty,
                packings.id as reference_id,
                '".addslashes(\App\Models\Packing::class)."' as reference_type,
                packings.remarks as remarks,
                packings.created_at as created_at
            ")
            ->whereNull('packings.deleted_at')
            ->whereNull('packing_items.deleted_at');

        $unpackingQuery = DB::table('unpacking_items')
            ->join('unpackings', 'unpackings.id', '=', 'unpacking_items.unpacking_id')
            ->join('products', 'products.id', '=', 'unpackings.product_id')
            ->selectRaw("
                unpackings.date as date,
                unpackings.product_id,
                products.name as product_name,
                products.code as product_code,
                'UNPACK' as operation,
                unpacking_items.pack_size_id,
                unpacking_items.pack_qty_snapshot,
                unpacking_items.pack_uom,
                unpacking_items.pack_count,
                (unpacking_items.pack_qty_snapshot * unpacking_items.pack_count) as bulk_qty,
                unpackings.id as reference_id,
                '".addslashes(\App\Models\Unpacking::class)."' as reference_type,
                unpackings.remarks as remarks,
                unpackings.created_at as created_at
            ")
            ->whereNull('unpackings.deleted_at')
            ->whereNull('unpacking_items.deleted_at');

        $union = $packingQuery->unionAll($unpackingQuery);

        $records = DB::query()
            ->fromSub($union, 'pu')
            ->when($this->productId, fn ($q) => $q->where('product_id', $this->productId))
            ->when($this->operationType, fn ($q) => $q->where('operation', $this->operationType))
            ->when($this->fromDate, fn ($q) => $q->whereDate('date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('date', '<=', $this->toDate))
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.packing.history', [
            'records' => $records,
            'products' => $this->products,
        ])->with(['title_name' => $this->title ?? 'Packing / Unpacking History']);
    }
}
