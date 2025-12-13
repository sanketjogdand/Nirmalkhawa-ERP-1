<?php

namespace App\Livewire\Inventory;

use App\Models\StockLedger;
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
        $this->currentCm = $inventoryService->getCurrentStock($this->cmProductId);
        $this->currentBm = $inventoryService->getCurrentStock($this->bmProductId);
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
        $timestamp = $data['date'].' '.now()->format('H:i:s');

        try {
            if ($qtyCm > $this->currentCm) {
                throw new RuntimeException('Not enough Cow Milk stock available.');
            }

            if ($qtyBm > $this->currentBm) {
                throw new RuntimeException('Not enough Buffalo Milk stock available.');
            }

            DB::transaction(function () use ($inventoryService, $qtyCm, $qtyBm, $total, $timestamp, $data) {
                if ($qtyCm > 0) {
                    $inventoryService->postOut($this->cmProductId, $qtyCm, StockLedger::TYPE_TRANSFER, [
                        'txn_datetime' => $timestamp,
                        'remarks' => trim(($data['remarks'] ?? '').' Transfer CM to MIX'),
                    ]);
                }

                if ($qtyBm > 0) {
                    $inventoryService->postOut($this->bmProductId, $qtyBm, StockLedger::TYPE_TRANSFER, [
                        'txn_datetime' => $timestamp,
                        'remarks' => trim(($data['remarks'] ?? '').' Transfer BM to MIX'),
                    ]);
                }

                $inventoryService->postIn($this->mixProductId, $total, StockLedger::TYPE_TRANSFER, [
                    'txn_datetime' => $timestamp,
                    'remarks' => trim(($data['remarks'] ?? '').' MIX stock received'),
                ]);
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
