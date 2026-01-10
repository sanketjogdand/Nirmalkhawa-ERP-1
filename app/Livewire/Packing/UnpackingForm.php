<?php

namespace App\Livewire\Packing;

use App\Models\PackSize;
use App\Models\Product;
use App\Models\Unpacking;
use App\Services\InventoryService;
use App\Services\PackInventoryService;
use App\Services\PackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use RuntimeException;

class UnpackingForm extends Component
{
    use AuthorizesRequests;

    public $title = 'Unpacking';
    public ?int $unpackingId = null;
    public bool $isLocked = false;
    public bool $isReadOnly = false;
    public ?int $originalProductId = null;
    public array $originalLineCounts = [];
    public $date;
    public $product_id = '';
    public $lines = [];
    public $remarks;
    public $products;
    public $packSizes = [];
    public $packInventory = [];

    public function mount($unpacking = null): void
    {
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $this->isReadOnly = request()->routeIs('unpackings.show');
        $this->unpackingId = $unpacking ? (int) $unpacking : null;

        if ($this->unpackingId) {
            $record = Unpacking::with(['items', 'product'])->findOrFail($this->unpackingId);
            $this->authorize($this->isReadOnly ? 'unpacking.view' : 'unpacking.update');

            $this->unpackingId = $record->id;
            $this->isLocked = (bool) $record->is_locked;
            $this->originalProductId = $record->product_id;
            $this->date = $record->date?->toDateString();
            $this->product_id = $record->product_id;
            $this->remarks = $record->remarks;
            $this->lines = $record->items->map(function ($item) {
                return [
                    'pack_size_id' => $item->pack_size_id,
                    'pack_count' => (int) $item->pack_count,
                ];
            })->toArray();

            $this->originalLineCounts = $record->items
                ->groupBy('pack_size_id')
                ->map(fn ($items) => $items->sum('pack_count'))
                ->toArray();

            if ($record->product && ! $this->products->contains('id', $record->product_id)) {
                $this->products->push($record->product);
            }
        } else {
            $this->authorize('unpacking.create');
            $this->date = now()->toDateString();
            $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
        }

        if ($this->product_id) {
            $this->loadPackContext();
        }
    }

    public function updatedProductId(): void
    {
        if (! $this->isEditable()) {
            return;
        }

        $this->loadPackContext();
        $this->lines = [['pack_size_id' => '', 'pack_count' => null]];
    }

    public function updatedDate(): void
    {
        if (! $this->isEditable() || ! $this->product_id) {
            return;
        }

        $this->loadPackContext();
    }

    public function updatedLines(): void
    {
        $this->validatePackAvailability();
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

    public function save(PackingService $packingService, InventoryService $inventoryService, PackInventoryService $packInventoryService)
    {
        $this->authorize($this->unpackingId ? 'unpacking.update' : 'unpacking.create');

        if (! $this->isEditable()) {
            $this->addError('form', 'Unpacking is locked or read-only.');
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

        foreach ($data['lines'] as $line) {
            $available = $this->getAvailablePackCount((int) $line['pack_size_id']);
            if ((int) $line['pack_count'] > $available) {
                $this->addError('lines', $this->formatPackShortageMessage(
                    (int) $line['pack_size_id'],
                    $available,
                    (int) $line['pack_count']
                ));
                return;
            }
        }

        try {
            if ($this->unpackingId) {
                $unpacking = Unpacking::findOrFail($this->unpackingId);
                $packingService->updateUnpacking($unpacking, [
                    'date' => $data['date'],
                    'product_id' => (int) $data['product_id'],
                    'remarks' => $data['remarks'] ?? null,
                ], $data['lines'], $packInventoryService);

                session()->flash('success', 'Unpacking updated.');
                return redirect()->route('unpackings.view');
            }

            $packingService->unpack([
                'date' => $data['date'],
                'product_id' => (int) $data['product_id'],
                'remarks' => $data['remarks'] ?? null,
            ], $data['lines'], $inventoryService, $packInventoryService);

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
        $asOfDate = $this->date ?: now()->toDateString();
        $inventoryMap = $this->product_id
            ? DB::table('pack_operations_view')
                ->where('product_id', $this->product_id)
                ->whereDate('txn_date', '<=', $asOfDate)
                ->selectRaw('pack_size_id, COALESCE(SUM(pack_count_in - pack_count_out), 0) as balance')
                ->groupBy('pack_size_id')
                ->pluck('balance', 'pack_size_id')
                ->toArray()
            : [];

        $lineSizeIds = collect($this->lines)->pluck('pack_size_id')->filter()->unique();

        $sizeQuery = PackSize::query()->where('product_id', $this->product_id);
        if ($inventoryMap || $lineSizeIds->isNotEmpty()) {
            $sizeQuery->where(function ($q) use ($inventoryMap, $lineSizeIds) {
                $q->where('is_active', true);

                $extraIds = array_merge(array_keys($inventoryMap), $lineSizeIds->all());
                if (! empty($extraIds)) {
                    $q->orWhereIn('id', $extraIds);
                }
            });
        } else {
            $sizeQuery->where('is_active', true);
        }

        $this->packSizes = $this->product_id
            ? $sizeQuery->orderBy('pack_qty')->get()->toArray()
            : [];

        $this->packInventory = $inventoryMap;

        if ($this->product_id && $this->originalProductId && (int) $this->product_id === $this->originalProductId) {
            foreach ($this->originalLineCounts as $packSizeId => $count) {
                $this->packInventory[$packSizeId] = ($this->packInventory[$packSizeId] ?? 0) + $count;
            }
        }

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
                $this->addError('lines', $this->formatPackShortageMessage(
                    (int) $line['pack_size_id'],
                    $available,
                    (int) $line['pack_count']
                ));
                return;
            }
        }

        $this->resetErrorBag('lines');
    }

    private function isEditable(): bool
    {
        return ! $this->isLocked && ! $this->isReadOnly;
    }

    private function formatPackShortageMessage(int $packSizeId, int $available, int $required): string
    {
        $product = $this->products?->firstWhere('id', (int) $this->product_id);
        $productName = $product?->name ?? ('Product ID '.$this->product_id);
        $packSize = collect($this->packSizes)->firstWhere('id', $packSizeId);
        $packLabel = $packSize
            ? number_format((float) $packSize['pack_qty'], 3).' '.$packSize['pack_uom']
            : ('Pack Size ID '.$packSizeId);
        $shortage = $required - $available;

        return sprintf(
            'Insufficient packs for %s %s. Available %d, required %d. Short by %d.',
            $productName,
            $packLabel,
            $available,
            $required,
            $shortage
        );
    }

    public function render()
    {
        return view('livewire.packing.unpacking-form')
            ->with(['title_name' => $this->title ?? 'Unpacking']);
    }
}
