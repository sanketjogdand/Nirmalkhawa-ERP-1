<?php

namespace App\Livewire\Packing;

use App\Models\PackSize;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\PackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use RuntimeException;

class PackingForm extends Component
{
    use AuthorizesRequests;

    public $title = 'Packing';
    public $date;
    public $product_id = '';
    public $lines = [];
    public $remarks;
    public $availableBulk = 0;
    public $products;
    public $packSizes = [];

    public function mount(InventoryService $inventoryService): void
    {
        $this->authorize('packing.create');
        $this->products = Product::where('can_stock', true)->where('is_active', true)->orderBy('name')->get();
        $this->date = now()->toDateString();
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];

        if ($this->product_id) {
            $this->loadContext($inventoryService);
        }
    }

    public function updatedProductId(InventoryService $inventoryService): void
    {
        $this->loadContext($inventoryService);
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
    }

    public function updatedLines(): void
    {
        $this->validateRemaining();
    }

    public function addLine(): void
    {
        $this->lines[] = ['pack_size_id' => '', 'pack_count' => null];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->validateRemaining();
    }

    public function getPackTotalQuantityProperty(): float
    {
        $packSizeLookup = collect($this->packSizes)->keyBy('id');

        return collect($this->lines)->sum(function ($line) use ($packSizeLookup) {
            if (empty($line['pack_size_id']) || empty($line['pack_count'])) {
                return 0;
            }

            $packSize = $packSizeLookup[$line['pack_size_id']] ?? null;
            if (! $packSize) {
                return 0;
            }

            return (float) $packSize['pack_qty'] * (int) $line['pack_count'];
        });
    }

    public function getRemainingBulkProperty(): float
    {
        return (float) $this->availableBulk - $this->packTotalQuantity;
    }

    public function save(PackingService $packingService, InventoryService $inventoryService)
    {
        $this->authorize('packing.create');

        $data = $this->validate([
            'date' => ['required', 'date'],
            'product_id' => ['required', 'exists:products,id'],
            'lines' => ['array', 'min:1'],
            'lines.*.pack_size_id' => ['required', 'exists:pack_sizes,id'],
            'lines.*.pack_count' => ['required', 'integer', 'gt:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'lines.*.pack_size_id' => 'pack size',
            'lines.*.pack_count' => 'number of packs',
        ]);

        $sizeIds = collect($data['lines'])->pluck('pack_size_id')->filter()->unique();
        $validSizeCount = PackSize::where('product_id', $data['product_id'])
            ->whereIn('id', $sizeIds)
            ->count();

        if ($validSizeCount !== $sizeIds->count()) {
            $this->addError('lines', 'Selected pack sizes do not belong to the chosen product.');
            return;
        }

        if ($this->remainingBulk < 0) {
            $this->addError('lines', 'Remaining bulk cannot be negative.');
            return;
        }

        try {
            $packingService->pack([
                'date' => $data['date'],
                'product_id' => (int) $data['product_id'],
                'remarks' => $data['remarks'] ?? null,
            ], $data['lines'], $inventoryService);

            $this->availableBulk = $inventoryService->getCurrentStock((int) $data['product_id']);
            $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
            $this->remarks = null;
            $this->packSizes = PackSize::where('product_id', $data['product_id'])->where('is_active', true)->orderBy('pack_qty')->get()->toArray();

            session()->flash('success', 'Packing saved and inventory updated.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }
    }

    private function loadContext(InventoryService $inventoryService): void
    {
        $this->availableBulk = $this->product_id ? $inventoryService->getCurrentStock((int) $this->product_id) : 0;
        $this->packSizes = $this->product_id
            ? PackSize::where('product_id', $this->product_id)->where('is_active', true)->orderBy('pack_qty')->get()->toArray()
            : [];
        $this->validateRemaining();
    }

    private function validateRemaining(): void
    {
        if ($this->remainingBulk < 0) {
            $this->addError('lines', 'Remaining bulk cannot be negative.');
        } else {
            $this->resetErrorBag('lines');
        }
    }

    public function render()
    {
        return view('livewire.packing.packing-form')
            ->with(['title_name' => $this->title ?? 'Packing']);
    }
}
