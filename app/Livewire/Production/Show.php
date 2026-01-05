<?php

namespace App\Livewire\Production;

use App\Models\ProductionBatch;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Production';

    public ProductionBatch $batch;
    public $ledgers;

    public function mount($production): void
    {
        $this->authorize('production.view');
        $this->batch = ProductionBatch::with([
            'outputProduct',
            'recipe',
            'inputs.materialProduct',
            'yieldBaseProduct',
            'lockedByUser',
            'createdByUser',
        ])->findOrFail($production);

        $inputIds = $this->batch->inputs->pluck('id');
        $this->ledgers = DB::table('inventory_ledger_view as il')
            ->leftJoin('products', 'products.id', '=', 'il.product_id')
            ->select('il.*', 'products.name as product_name')
            ->where(function ($query) use ($inputIds) {
                $query->where(function ($sub) {
                    $sub->where('il.ref_table', 'production_batches')
                        ->where('il.ref_id', $this->batch->id);
                });

                if ($inputIds->isNotEmpty()) {
                    $query->orWhere(function ($sub) use ($inputIds) {
                        $sub->where('il.ref_table', 'production_inputs')
                            ->whereIn('il.ref_id', $inputIds);
                    });
                }
            })
            ->orderBy('il.txn_date')
            ->orderBy('il.created_at')
            ->orderBy('il.ref_id')
            ->get();
    }

    public function render()
    {
        return view('livewire.production.show', ['batch' => $this->batch, 'ledgers' => $this->ledgers])
            ->with(['title_name' => $this->title ?? 'Production']);
    }
}
