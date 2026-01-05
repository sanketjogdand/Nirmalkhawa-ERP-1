<?php

namespace App\Livewire\Inventory;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

class TransferToMix extends Component
{
    use AuthorizesRequests;

    public $title = 'Transfer to Mix';
    public $date;
    public $qty_cm_ltr = 0;
    public $qty_bm_ltr = 0;
    public $remarks;
    public $currentCm = 0;
    public $currentBm = 0;

    private ?int $cmProductId = null;
    private ?int $bmProductId = null;
    private ?int $mixProductId = null;

    public function mount(InventoryService $inventoryService): void
    {
        $this->authorize('inventory.transfer');
        $this->date = now()->toDateString();

        $this->ensureProductIds($inventoryService);
        $this->refreshStocks($inventoryService);
    }

    public function refreshStocks(InventoryService $inventoryService): void
    {
        $this->ensureProductIds($inventoryService);
        $this->currentCm = $inventoryService->getOnHand($this->cmProductId);
        $this->currentBm = $inventoryService->getOnHand($this->bmProductId);
    }

    public function save(InventoryService $inventoryService)
    {
        $this->authorize('inventory.transfer');

        $data = $this->validate([
            'date' => ['required', 'date'],
            'qty_cm_ltr' => ['nullable', 'numeric', 'min:0'],
            'qty_bm_ltr' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ], [], [
            'qty_cm_ltr' => 'CM quantity',
            'qty_bm_ltr' => 'BM quantity',
        ]);

        $qtyCm = (float) ($data['qty_cm_ltr'] ?? 0);
        $qtyBm = (float) ($data['qty_bm_ltr'] ?? 0);
        $total = $qtyCm + $qtyBm;

        if ($total <= 0) {
            $this->addError('qty_cm_ltr', 'Enter at least one quantity greater than zero.');
            return;
        }

        $this->ensureProductIds($inventoryService);

        try {
            if ($qtyCm > $this->currentCm) {
                throw new RuntimeException('Not enough Cow Milk stock available.');
            }

            if ($qtyBm > $this->currentBm) {
                throw new RuntimeException('Not enough Buffalo Milk stock available.');
            }

            DB::transaction(function () use ($qtyCm, $qtyBm, $total, $data) {
                $adjustment = StockAdjustment::create([
                    'adjustment_date' => $data['date'],
                    'reason' => 'Transfer to MIX',
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $lines = [];
                $now = now();

                if ($qtyCm > 0) {
                    $lines[] = [
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $this->cmProductId,
                        'direction' => StockAdjustmentLine::DIRECTION_OUT,
                        'qty' => $qtyCm,
                        'uom' => 'LTR',
                        'remarks' => trim(($data['remarks'] ?? '').' Transfer CM to MIX'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($qtyBm > 0) {
                    $lines[] = [
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $this->bmProductId,
                        'direction' => StockAdjustmentLine::DIRECTION_OUT,
                        'qty' => $qtyBm,
                        'uom' => 'LTR',
                        'remarks' => trim(($data['remarks'] ?? '').' Transfer BM to MIX'),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $lines[] = [
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id' => $this->mixProductId,
                    'direction' => StockAdjustmentLine::DIRECTION_IN,
                    'qty' => $total,
                    'uom' => 'LTR',
                    'remarks' => trim(($data['remarks'] ?? '').' MIX stock received'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                StockAdjustmentLine::insert($lines);
            });

            $this->refreshStocks($inventoryService);
            $this->qty_cm_ltr = 0;
            $this->qty_bm_ltr = 0;
            session()->flash('success', 'Transfer completed.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.inventory.transfer-to-mix')
            ->with(['title_name' => $this->title ?? 'Transfer to Mix']);
    }

    private function ensureProductIds(InventoryService $inventoryService): void
    {
        if (! $this->cmProductId) {
            $this->cmProductId = $inventoryService->getMilkProduct('CM')->id;
        }

        if (! $this->bmProductId) {
            $this->bmProductId = $inventoryService->getMilkProduct('BM')->id;
        }

        if (! $this->mixProductId) {
            $this->mixProductId = $inventoryService->getMilkProduct('MIX')->id;
        }
    }
}
