<?php

namespace App\Livewire\Packing;

use App\Models\PackInventory;
use App\Models\PackSize;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\PackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use RuntimeException;

class UnpackingForm extends Component
{
    use AuthorizesRequests;

    public $title = 'Unpacking';
    public $date;
    public $product_id = '';
    public $lines = [];
    public $remarks;
    public $products;
    public $packSizes = [];
    public $packInventory = [];

    public function mount(): void
    {
        $this->authorize('unpacking.create');
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $this->date = now()->toDateString();
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
    }

    public function updatedProductId(): void
    {
        $this->loadPackContext();
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
    }

    public function updatedLines(): void
    {
        $this->validatePackAvailability();
    }

    public function addLine(): void
    {
        $this->lines[] = ['pack_size_id' => '', 'pack_count' => null];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->validatePackAvailability();
    }

    public function getTotalBulkReturnProperty(): float
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

    public function getAvailablePackCount(int $packSizeId): int
    {
        return (int) ($this->packInventory[$packSizeId] ?? 0);
    }

    public function save(PackingService $packingService, InventoryService $inventoryService)
    {
        $this->authorize('unpacking.create');

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

        foreach ($data['lines'] as $line) {
            $available = $this->getAvailablePackCount((int) $line['pack_size_id']);
            if ((int) $line['pack_count'] > $available) {
                $this->addError('lines', 'Not enough packs available for the selected size.');
                return;
            }
        }

        try {
            $packingService->unpack([
                'date' => $data['date'],
                'product_id' => (int) $data['product_id'],
                'remarks' => $data['remarks'] ?? null,
            ], $data['lines'], $inventoryService);

            $this->loadPackContext();
            $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
            $this->remarks = null;

            session()->flash('success', 'Unpacking saved and inventory updated.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
        }
    }

    private function loadPackContext(): void
    {
        $inventoryMap = $this->product_id
            ? PackInventory::where('product_id', $this->product_id)->pluck('pack_count', 'pack_size_id')->toArray()
            : [];

        $sizeQuery = PackSize::query()->where('product_id', $this->product_id);
        if ($inventoryMap) {
            $sizeQuery->where(function ($q) use ($inventoryMap) {
                $q->where('is_active', true)
                    ->orWhereIn('id', array_keys($inventoryMap));
            });
        } else {
            $sizeQuery->where('is_active', true);
        }

        $this->packSizes = $this->product_id
            ? $sizeQuery->orderBy('pack_qty')->get()->toArray()
            : [];

        $this->packInventory = $inventoryMap;

        $this->validatePackAvailability();
    }

    private function validatePackAvailability(): void
    {
        foreach ($this->lines as $line) {
            if (empty($line['pack_size_id']) || empty($line['pack_count'])) {
                continue;
            }

            $available = $this->getAvailablePackCount((int) $line['pack_size_id']);
            if ((int) $line['pack_count'] > $available) {
                $this->addError('lines', 'Not enough packs available for the selected size.');
                return;
            }
        }

        $this->resetErrorBag('lines');
    }

    public function render()
    {
        return view('livewire.packing.unpacking-form')
            ->with(['title_name' => $this->title ?? 'Unpacking']);
    }
}
