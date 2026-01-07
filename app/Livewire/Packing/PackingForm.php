<?php

namespace App\Livewire\Packing;

use App\Models\Packing;
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
    public ?int $packingId = null;
    public bool $isLocked = false;
    public bool $isReadOnly = false;
    public ?int $originalProductId = null;
    public float $originalTotalBulkQty = 0.0;
    public $date;
    public $product_id = '';
    public $lines = [];
    public $remarks;
    public $availableBulk = 0;
    public $products;
    public $packSizes = [];

    public function mount(InventoryService $inventoryService, $packing = null): void
    {
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $this->isReadOnly = request()->routeIs('packings.show');
        $this->packingId = $packing ? (int) $packing : null;

        if ($this->packingId) {
            $record = Packing::with(['items', 'product'])->findOrFail($this->packingId);
            $this->authorize($this->isReadOnly ? 'packing.view' : 'packing.update');

            $this->packingId = $record->id;
            $this->isLocked = (bool) $record->is_locked;
            $this->originalProductId = $record->product_id;
            $this->originalTotalBulkQty = (float) $record->total_bulk_qty;
            $this->date = $record->date?->toDateString();
            $this->product_id = $record->product_id;
            $this->remarks = $record->remarks;
            $this->lines = $record->items->map(function ($item) {
                return [
                    'pack_size_id' => $item->pack_size_id,
                    'pack_count' => (int) $item->pack_count,
                ];
            })->toArray();

            if ($record->product && ! $this->products->contains('id', $record->product_id)) {
                $this->products->push($record->product);
            }
        } else {
            $this->authorize('packing.create');
            $this->date = now()->toDateString();
            $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
        }

        if ($this->product_id) {
            $this->loadContext($inventoryService);
        }
    }

    public function updatedProductId(InventoryService $inventoryService): void
    {
        if (! $this->isEditable()) {
            return;
        }

        $this->loadContext($inventoryService);
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
    }

    public function updatedLines(): void
    {
        $this->validateRemaining();
    }

    public function addLine(): void
    {
        if (! $this->isEditable()) {
            return;
        }

        $this->lines[] = ['pack_size_id' => '', 'pack_count' => null];
    }

    public function removeLine(int $index): void
    {
        if (! $this->isEditable()) {
            return;
        }

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
        $this->authorize($this->packingId ? 'packing.update' : 'packing.create');

        if (! $this->isEditable()) {
            $this->addError('form', 'Packing is locked or read-only.');
            return;
        }

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
            if ($this->packingId) {
                $packing = Packing::findOrFail($this->packingId);
                $packingService->updatePacking($packing, [
                    'date' => $data['date'],
                    'product_id' => (int) $data['product_id'],
                    'remarks' => $data['remarks'] ?? null,
                ], $data['lines'], $inventoryService);

                session()->flash('success', 'Packing updated.');
                return redirect()->route('packings.view');
            }

            $packingService->pack([
                'date' => $data['date'],
                'product_id' => (int) $data['product_id'],
                'remarks' => $data['remarks'] ?? null,
            ], $data['lines'], $inventoryService);

            $this->availableBulk = $inventoryService->getOnHand((int) $data['product_id']);
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
        $this->availableBulk = $this->product_id ? $inventoryService->getOnHand((int) $this->product_id) : 0;

        if ($this->product_id && $this->originalProductId && (int) $this->product_id === $this->originalProductId) {
            $this->availableBulk += $this->originalTotalBulkQty;
        }

        $lineSizeIds = collect($this->lines)->pluck('pack_size_id')->filter()->unique();

        $this->packSizes = $this->product_id
            ? PackSize::where('product_id', $this->product_id)
                ->when($lineSizeIds->isNotEmpty(), function ($query) use ($lineSizeIds) {
                    $query->where(function ($q) use ($lineSizeIds) {
                        $q->where('is_active', true)->orWhereIn('id', $lineSizeIds);
                    });
                }, fn ($query) => $query->where('is_active', true))
                ->orderBy('pack_qty')
                ->get()
                ->toArray()
            : [];
        $this->validateRemaining();
    }

    private function isEditable(): bool
    {
        return ! $this->isLocked && ! $this->isReadOnly;
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
