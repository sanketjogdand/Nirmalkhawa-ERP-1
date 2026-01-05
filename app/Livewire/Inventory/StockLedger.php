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
    public array $totals = [
        'in' => 0.0,
        'out' => 0.0,
        'net' => 0.0,
    ];
    public array $runningBalances = [];

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
        $this->runningBalances = [];

        $filtered = DB::table('inventory_ledger_view as il')
            ->leftJoin('products', 'products.id', '=', 'il.product_id')
            ->when($this->productId, fn ($q) => $q->where('il.product_id', $this->productId))
            ->when($this->txnType, fn ($q) => $q->where('il.txn_type', $this->txnType))
            ->when($this->fromDate, fn ($q) => $q->whereDate('il.txn_date', '>=', $this->fromDate))
            ->when($this->toDate, fn ($q) => $q->whereDate('il.txn_date', '<=', $this->toDate));

        $summary = (clone $filtered)
            ->selectRaw('COALESCE(SUM(qty_in), 0) as total_in, COALESCE(SUM(qty_out), 0) as total_out')
            ->first();

        $this->totals = [
            'in' => (float) ($summary->total_in ?? 0),
            'out' => (float) ($summary->total_out ?? 0),
            'net' => (float) ($summary->total_in ?? 0) - (float) ($summary->total_out ?? 0),
        ];

        $ledgers = (clone $filtered)
            ->select('il.*', 'products.name as product_name', 'products.code as product_code')
            ->orderByDesc('il.txn_date')
            ->orderByDesc('il.created_at')
            // ->orderByDesc('il.ref_table')
            // ->orderByDesc('il.ref_id')
            ->paginate($this->perPage);

        $running = 0.0;
        $startIndex = ($ledgers->currentPage() - 1) * $ledgers->perPage();
        foreach ($ledgers->items() as $index => $row) {
            $running += (float) $row->qty_in - (float) $row->qty_out;
            $this->runningBalances[$startIndex + $index] = $running;
        }

        return view('livewire.inventory.stock-ledger', [
            'ledgers' => $ledgers,
        ])->with(['title_name' => $this->title ?? 'Stock Ledger']);
    }
}
