<?php

namespace App\Livewire\Dispatch;

use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\DispatchLine;
use App\Models\PackSize;
use App\Models\Product;
use App\Services\DispatchService;
use App\Services\InventoryService;
use App\Services\PackInventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Dispatch / Outward';

    public ?int $dispatchId = null;
    public ?string $dispatch_no = null;
    public $dispatch_date;
    public $delivery_mode;
    public $vehicle_no;
    public $driver_name;
    public $remarks;
    public $status;
    public bool $isLocked = false;

    public array $lines = [];

    public $customers;
    public $products;
    public $packSizes;
    public array $packSizesByProduct = [];
    public array $packSizeLookup = [];
    public array $invoiceOptions = [];

    public array $bulkAvailabilityMap = [];
    public array $packAvailabilityMap = [];

    public function mount($dispatch = null): void
    {
        $this->customers = Customer::orderBy('name')->get();
        $this->products = Product::where('can_sell', true)->orderBy('name')->get();
        $this->packSizes = PackSize::where('is_active', true)->orderBy('pack_qty')->get();
        $this->packSizesByProduct = $this->packSizes->groupBy('product_id')->map->values()->toArray();
        $this->packSizeLookup = $this->packSizes->keyBy('id')->toArray();

        if ($dispatch) {
            $record = Dispatch::with('lines')->findOrFail($dispatch);
            $this->authorize('dispatch.update');

            $this->dispatchId = $record->id;
            $this->dispatch_no = $record->dispatch_no;
            $this->isLocked = $record->is_locked;

            $this->fill($record->only([
                'dispatch_date',
                'delivery_mode',
                'vehicle_no',
                'driver_name',
                'remarks',
                'status',
            ]));

            $this->dispatch_date = $record->dispatch_date ? $record->dispatch_date->toDateString() : null;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'customer_id' => $line->customer_id,
                    'invoice_id' => $line->invoice_id,
                    'product_id' => $line->product_id,
                    'sale_mode' => $line->sale_mode,
                    'qty_bulk' => $line->sale_mode === DispatchLine::MODE_BULK ? (float) $line->qty_bulk : null,
                    'uom' => $line->uom,
                    'pack_size_id' => $line->pack_size_id,
                    'pack_count' => $line->pack_count,
                    'computed_total_qty' => (float) $line->computed_total_qty,
                ];
            })->toArray();
        } else {
            $this->authorize('dispatch.create');
            $this->dispatch_date = now()->toDateString();
            $this->delivery_mode = Dispatch::DELIVERY_SELF;
            $this->status = Dispatch::STATUS_POSTED;
            $this->lines = [[
                'customer_id' => '',
                'invoice_id' => '',
                'product_id' => '',
                'sale_mode' => DispatchLine::MODE_BULK,
                'qty_bulk' => null,
                'uom' => null,
                'pack_size_id' => '',
                'pack_count' => null,
                'computed_total_qty' => 0,
            ]];
        }

        $this->refreshLineUoms();
        $this->refreshAvailability();
    }

    public function updatedLines($value, $name): void
    {
        if (! str_starts_with($name, 'lines.')) {
            return;
        }

        $parts = explode('.', $name);
        $index = (int) ($parts[1] ?? 0);
        $field = $parts[2] ?? null;

        if (! isset($this->lines[$index])) {
            return;
        }

        if ($field === 'sale_mode') {
            if ($value === DispatchLine::MODE_PACK) {
                $this->lines[$index]['qty_bulk'] = null;
                $this->lines[$index]['uom'] = null;
            } else {
                $this->lines[$index]['pack_size_id'] = '';
                $this->lines[$index]['pack_count'] = null;
                $this->lines[$index]['computed_total_qty'] = $this->lines[$index]['qty_bulk'] ?? 0;
            }
        }

        if ($field === 'product_id') {
            $product = $this->products->firstWhere('id', (int) $value);
            $this->lines[$index]['uom'] = $product->uom ?? null;
        }

        if ($field === 'pack_size_id' || $field === 'pack_count') {
            $this->refreshPackTotals($index);
        }

        if ($field === 'qty_bulk') {
            $this->lines[$index]['computed_total_qty'] = (float) ($this->lines[$index]['qty_bulk'] ?? 0);
        }

        if ($field === 'customer_id') {
            $this->loadInvoicesForCustomer($index, (int) $value);
        }

        $this->refreshLineUoms();
        $this->refreshAvailability();
    }

    public function addLine(): void
    {
        $this->lines[] = [
            'customer_id' => '',
            'invoice_id' => '',
            'product_id' => '',
            'sale_mode' => DispatchLine::MODE_BULK,
            'qty_bulk' => null,
            'uom' => null,
            'pack_size_id' => '',
            'pack_count' => null,
            'computed_total_qty' => 0,
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index], $this->invoiceOptions[$index]);
        $this->lines = array_values($this->lines);
        $this->invoiceOptions = array_values($this->invoiceOptions);
        $this->refreshAvailability();
    }

    public function saveAndPost(DispatchService $dispatchService, InventoryService $inventoryService, PackInventoryService $packInventoryService)
    {
        $this->status = Dispatch::STATUS_POSTED;
        return $this->saveInternal($dispatchService, $inventoryService, $packInventoryService);
    }

    public function render()
    {
        $this->refreshAvailability();

        return view('livewire.dispatch.form')
            ->with([
                'title_name' => $this->title ?? 'Dispatch / Outward',
                'packSizesByProduct' => $this->packSizesByProduct,
                'bulkAvailabilityMap' => $this->bulkAvailabilityMap,
                'packAvailabilityMap' => $this->packAvailabilityMap,
            ]);
    }

    private function saveInternal(DispatchService $dispatchService, InventoryService $inventoryService, PackInventoryService $packInventoryService)
    {
        $this->authorize($this->dispatchId ? 'dispatch.update' : 'dispatch.create');

        if ($this->isLocked) {
            $this->addError('form', 'Locked dispatch cannot be edited.');
            return;
        }

        $this->lines = collect($this->lines)->map(function ($line) {
            if (isset($line['invoice_id']) && $line['invoice_id'] === '') {
                $line['invoice_id'] = null;
            }

            return $line;
        })->toArray();

        $data = $this->validate($this->rules(), [], [
            'lines.*.customer_id' => 'customer',
            'lines.*.product_id' => 'product',
            'lines.*.qty_bulk' => 'bulk quantity',
            'lines.*.pack_size_id' => 'pack size',
            'lines.*.pack_count' => 'pack count',
        ]);

        foreach ($data['lines'] as $index => $line) {
            if (($line['sale_mode'] ?? '') === DispatchLine::MODE_BULK && empty($line['qty_bulk'])) {
                $this->addError('lines.'.$index.'.qty_bulk', 'Bulk quantity is required.');
                return;
            }

            if (($line['sale_mode'] ?? '') === DispatchLine::MODE_PACK) {
                if (empty($line['pack_size_id'])) {
                    $this->addError('lines.'.$index.'.pack_size_id', 'Pack size is required for pack mode.');
                    return;
                }
                if (empty($line['pack_count']) || $line['pack_count'] <= 0) {
                    $this->addError('lines.'.$index.'.pack_count', 'Pack count must be greater than zero.');
                    return;
                }
            }
        }

        $payload = [
            'dispatch_date' => $data['dispatch_date'],
            'delivery_mode' => $data['delivery_mode'],
            'vehicle_no' => $data['vehicle_no'] ?? null,
            'driver_name' => $data['driver_name'] ?? null,
            'remarks' => isset($data['remarks']) && trim((string) $data['remarks']) !== '' ? trim($data['remarks']) : null,
            'status' => $this->status,
        ];

        $productIds = collect($data['lines'])->pluck('product_id')->unique()->filter();
        if ($productIds->isNotEmpty()) {
            $productMap = Product::whereIn('id', $productIds)->get(['id', 'can_sell'])->keyBy('id');
            foreach ($data['lines'] as $index => $line) {
                $product = $productMap->get((int) $line['product_id']);
                if (! $product || ! $product->can_sell) {
                    $this->addError('lines.'.$index.'.product_id', 'Selected product cannot be dispatched/sold.');
                    return;
                }
            }
        }

        try {
            if ($this->dispatchId) {
                $dispatch = Dispatch::findOrFail($this->dispatchId);
                $dispatchService->update($dispatch, $payload, $data['lines'], $inventoryService, $packInventoryService);
                session()->flash('success', 'Dispatch updated.');
            } else {
                $dispatch = $dispatchService->create($payload, $data['lines'], $inventoryService, $packInventoryService);
                session()->flash('success', 'Dispatch saved.');
            }
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
            return;
        }

        return redirect()->route('dispatches.view');
    }

    private function refreshPackTotals(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $line = $this->lines[$index];
        $packSizeId = (int) ($line['pack_size_id'] ?? 0);
        $packCount = (int) ($line['pack_count'] ?? 0);

        if ($packSizeId && isset($this->packSizeLookup[$packSizeId])) {
            $pack = $this->packSizeLookup[$packSizeId];
            $this->lines[$index]['computed_total_qty'] = round((float) $pack['pack_qty'] * $packCount, 3);
            $this->lines[$index]['uom'] = $pack['pack_uom'];
        } else {
            $this->lines[$index]['computed_total_qty'] = 0;
        }
    }

    private function refreshAvailability(): void
    {
        $productIds = collect($this->lines)->pluck('product_id')->filter()->unique();
        $packSizeIds = collect($this->lines)->pluck('pack_size_id')->filter()->unique();

        $inventoryService = app(InventoryService::class);
        $packInventoryService = app(PackInventoryService::class);

        $isEditingPosted = $this->dispatchId && $this->status === Dispatch::STATUS_POSTED;

        $lineTotals = collect($this->lines)
            ->where('sale_mode', DispatchLine::MODE_BULK)
            ->groupBy('product_id')
            ->map(function ($items) {
                return $items->sum(fn ($line) => (float) ($line['qty_bulk'] ?? $line['computed_total_qty'] ?? 0));
            });

        $this->bulkAvailabilityMap = [];
        foreach ($productIds as $productId) {
            $available = $inventoryService->getOnHand((int) $productId);
            if ($isEditingPosted) {
                $available += (float) ($lineTotals[$productId] ?? 0);
            }

            $this->bulkAvailabilityMap[$productId] = $available;
        }

        $packLineGroups = collect($this->lines)
            ->where('sale_mode', DispatchLine::MODE_PACK)
            ->filter(fn ($line) => ! empty($line['product_id']) && ! empty($line['pack_size_id']))
            ->groupBy(fn ($line) => $line['product_id'].'-'.$line['pack_size_id']);

        $this->packAvailabilityMap = [];
        foreach ($packLineGroups as $key => $lines) {
            [$productId, $packSizeId] = array_map('intval', explode('-', $key));
            $available = $packInventoryService->getPackOnHand($productId, $packSizeId);

            if ($isEditingPosted) {
                $available += (int) $lines->sum(fn ($line) => (int) ($line['pack_count'] ?? 0));
            }

            $this->packAvailabilityMap[$key] = $available;
        }
    }

    private function refreshLineUoms(): void
    {
        $productLookup = $this->products->keyBy('id');

        foreach ($this->lines as $i => $line) {
            if (($line['sale_mode'] ?? '') === DispatchLine::MODE_PACK && ! empty($line['pack_size_id'])) {
                $pack = $this->packSizeLookup[$line['pack_size_id']] ?? null;
                if ($pack) {
                    $this->lines[$i]['uom'] = $pack['pack_uom'] ?? $this->lines[$i]['uom'];
                }
            } elseif (! empty($line['product_id'])) {
                $product = $productLookup->get((int) $line['product_id']);
                if ($product) {
                    $this->lines[$i]['uom'] = $product->uom ?? ($this->lines[$i]['uom'] ?? null);
                }
            }
        }
    }

    private function loadInvoicesForCustomer(int $index, int $customerId): void
    {
        if (! class_exists('App\\Models\\SalesInvoice') || ! $customerId) {
            $this->invoiceOptions[$index] = [];
            return;
        }

        $invoiceClass = app('App\\Models\\SalesInvoice');
        $invoices = $invoiceClass::where('customer_id', $customerId)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->take(50)
            ->get(['id', 'invoice_no', 'invoice_date']);

        $this->invoiceOptions[$index] = $invoices->map(function ($invoice) {
            $date = $invoice->invoice_date instanceof \Carbon\Carbon
                ? $invoice->invoice_date->toDateString()
                : ($invoice->invoice_date ?? '');

            return [
                'id' => $invoice->id,
                'label' => trim(($invoice->invoice_no ?? 'Invoice').' '.($date ?: '')),
            ];
        })->toArray();
    }

    private function rules(): array
    {
        return [
            'dispatch_date' => ['required', 'date'],
            'delivery_mode' => ['required', Rule::in([Dispatch::DELIVERY_SELF, Dispatch::DELIVERY_COMPANY])],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.customer_id' => ['required', 'exists:customers,id'],
            'lines.*.invoice_id' => ['nullable', 'integer'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.sale_mode' => ['required', Rule::in([DispatchLine::MODE_BULK, DispatchLine::MODE_PACK])],
            'lines.*.qty_bulk' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.pack_size_id' => ['nullable', 'exists:pack_sizes,id'],
            'lines.*.pack_count' => ['nullable', 'integer', 'gte:0'],
        ];
    }
}
