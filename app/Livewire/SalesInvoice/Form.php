<?php

namespace App\Livewire\SalesInvoice;

use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\DispatchLine;
use App\Models\PackSize;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Sales Invoice';

    public ?int $invoiceId = null;
    public ?string $invoice_no = null;
    public ?string $invoiceNoPreview = null;
    public $invoice_date;
    public ?int $customer_id = null;
    public $remarks;
    public $status;
    public bool $isLocked = false;

    public float $subtotal = 0;
    public float $total_gst = 0;
    public float $grand_total = 0;

    public array $lines = [];

    public $customers;
    public $products;
    public $packSizes;
    public array $packSizesByProduct = [];
    public array $packSizeLookup = [];
    public array $gstRates = [0, 5, 18];

    public array $dispatchOptions = [];
    public array $selectedDispatches = [];

    public function mount($salesInvoice = null): void
    {
        $this->customers = Customer::orderBy('name')->get();
        $this->products = Product::where('can_sell', true)->orderBy('name')->get();
        $this->packSizes = PackSize::where('is_active', true)->orderBy('pack_qty')->get();
        $this->packSizesByProduct = $this->packSizes->groupBy('product_id')->map->values()->toArray();
        $this->packSizeLookup = $this->packSizes->keyBy('id')->toArray();

        if ($salesInvoice) {
            $record = SalesInvoice::with('lines.product', 'lines.packSize', 'lines.dispatchLine')->findOrFail($salesInvoice);
            $this->authorize('salesinvoice.update');

            $this->invoiceId = $record->id;
            $this->invoice_no = $record->invoice_no;
            $this->invoice_date = $record->invoice_date ? $record->invoice_date->toDateString() : now()->toDateString();
            $this->customer_id = $record->customer_id;
            $this->remarks = $record->remarks;
            $this->status = $record->status;
            $this->isLocked = $record->is_locked;
            $this->subtotal = (float) $record->subtotal;
            $this->total_gst = (float) $record->total_gst;
            $this->grand_total = (float) $record->grand_total;

            $this->lines = $record->lines->map(function ($line) {
                $gst = in_array((float) $line->gst_rate_percent, $this->gstRates, true) ? (float) $line->gst_rate_percent : null;
                return [
                    'product_id' => $line->product_id,
                    'sale_mode' => $line->sale_mode,
                    'qty_bulk' => $line->sale_mode === SalesInvoiceLine::MODE_BULK ? (float) $line->qty_bulk : null,
                    'pack_size_id' => $line->sale_mode === SalesInvoiceLine::MODE_PACK ? $line->pack_size_id : '',
                    'pack_count' => $line->sale_mode === SalesInvoiceLine::MODE_PACK ? $line->pack_count : null,
                    'computed_total_qty' => (float) $line->computed_total_qty,
                    'rate_per_kg' => (float) $line->rate_per_kg,
                    'gst_rate_percent' => $gst,
                    'dispatch_id' => $line->dispatch_id,
                    'dispatch_line_id' => $line->dispatch_line_id,
                    'uom' => $line->uom,
                    'taxable_amount' => (float) $line->taxable_amount,
                    'gst_amount' => (float) $line->gst_amount,
                    'line_total' => (float) $line->line_total,
                    'source_dispatch_qty' => $line->dispatchLine->computed_total_qty ?? null,
                    'source_dispatch_pack_count' => $line->dispatchLine->pack_count ?? null,
                ];
            })->toArray();
        } else {
            $this->authorize('salesinvoice.create');
            $this->invoice_date = now()->toDateString();
            $this->status = SalesInvoice::STATUS_DRAFT;
            $this->lines = [
                $this->blankLine(),
            ];
            $this->refreshInvoiceNoPreview();
        }

        $this->loadDispatchOptions();
        $this->refreshTotals();
    }

    public function updated($name, $value): void
    {
        if ($name === 'invoice_date' && ! $this->invoiceId) {
            $this->refreshInvoiceNoPreview();
        }

        if ($name === 'customer_id') {
            $this->loadDispatchOptions();
        }
    }

    public function updatedLines($value, $name): void
    {
        if (! str_starts_with($name, 'lines.')) {
            // Livewire sometimes passes child paths like "0.rate_per_kg"; normalize to "lines.0.rate_per_kg"
            if (preg_match('/^(\\d+)\\./', $name)) {
                $name = 'lines.'.$name;
            } else {
                return;
            }
        }

        $parts = explode('.', $name);
        $index = (int) ($parts[1] ?? 0);
        $field = $parts[2] ?? null;

        if (! isset($this->lines[$index])) {
            return;
        }

        if ($field === 'sale_mode') {
            if ($value === SalesInvoiceLine::MODE_PACK) {
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
            if ($product && (! isset($this->lines[$index]['gst_rate_percent']) || $this->lines[$index]['gst_rate_percent'] === null)) {
                $defaultGst = $product->default_gst_rate ?? null;
                $this->lines[$index]['gst_rate_percent'] = $defaultGst !== null && in_array((float) $defaultGst, $this->gstRates, true)
                    ? (float) $defaultGst
                    : 0;
            }
        }

        if ($field === 'pack_size_id' || $field === 'pack_count') {
            $this->refreshPackTotals($index);
        }

        if ($field === 'qty_bulk') {
            $this->lines[$index]['computed_total_qty'] = (float) ($this->lines[$index]['qty_bulk'] ?? 0);
        }

        if (in_array($field, ['rate_per_kg', 'gst_rate_percent'], true)) {
            $this->recalculateLine($index);
            $this->refreshTotals();
            return;
        }

        $this->recalculateLine($index);
        $this->refreshTotals();
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
        $this->refreshTotals();
    }

    public function importDispatches(): void
    {
        if (! $this->customer_id || empty($this->selectedDispatches)) {
            return;
        }

        $dispatches = Dispatch::with(['lines' => function ($query) {
            $query->where('customer_id', $this->customer_id)->with('product');
        }])
            ->whereIn('id', $this->selectedDispatches)
            ->where('status', Dispatch::STATUS_POSTED)
            ->get();

        $existingDispatchLineIds = collect($this->lines)->pluck('dispatch_line_id')->filter()->unique()->values()->all();

        foreach ($dispatches as $dispatch) {
            foreach ($dispatch->lines as $line) {
                if (in_array($line->id, $existingDispatchLineIds, true)) {
                    continue;
                }

                $this->lines[] = [
                    'product_id' => $line->product_id,
                    'sale_mode' => $line->sale_mode,
                    'qty_bulk' => $line->sale_mode === DispatchLine::MODE_BULK ? (float) $line->qty_bulk : null,
                    'pack_size_id' => $line->sale_mode === DispatchLine::MODE_PACK ? $line->pack_size_id : '',
                    'pack_count' => $line->sale_mode === DispatchLine::MODE_PACK ? (int) $line->pack_count : null,
                    'computed_total_qty' => (float) $line->computed_total_qty,
                    'rate_per_kg' => null,
                    'gst_rate_percent' => in_array($line->product->default_gst_rate ?? 0, $this->gstRates, true) ? ($line->product->default_gst_rate ?? 0) : 0,
                    'dispatch_id' => $dispatch->id,
                    'dispatch_line_id' => $line->id,
                    'uom' => $line->uom,
                    'taxable_amount' => 0,
                    'gst_amount' => 0,
                    'line_total' => 0,
                    'source_dispatch_qty' => (float) $line->computed_total_qty,
                    'source_dispatch_pack_count' => $line->pack_count,
                ];
                $this->recalculateLine(count($this->lines) - 1);
            }
        }

        $this->selectedDispatches = [];
        $this->refreshTotals();
    }

    public function save(SalesInvoiceService $invoiceService)
    {
        $this->status = $this->invoiceId && $this->status === SalesInvoice::STATUS_POSTED
            ? SalesInvoice::STATUS_POSTED
            : SalesInvoice::STATUS_DRAFT;
        return $this->saveInternal($invoiceService);
    }

    public function saveAndPost(SalesInvoiceService $invoiceService)
    {
        $this->status = SalesInvoice::STATUS_POSTED;
        return $this->saveInternal($invoiceService);
    }

    public function render()
    {
        return view('livewire.sales-invoice.form', [
            'title_name' => $this->title ?? 'Sales Invoice',
            'packSizesByProduct' => $this->packSizesByProduct,
        ]);
    }

    private function saveInternal(SalesInvoiceService $invoiceService)
    {
        $this->authorize($this->invoiceId ? 'salesinvoice.update' : 'salesinvoice.create');

        if ($this->isLocked) {
            $this->addError('form', 'Locked invoice cannot be edited.');
            return;
        }

        foreach ($this->lines as $i => $line) {
            $this->recalculateLine($i);
        }
        $this->refreshTotals();

        $this->lines = collect($this->lines)->map(function ($line) {
            if (isset($line['dispatch_line_id']) && $line['dispatch_line_id'] === '') {
                $line['dispatch_line_id'] = null;
            }
            if (isset($line['pack_size_id']) && $line['pack_size_id'] === '') {
                $line['pack_size_id'] = null;
            }
            if (array_key_exists('gst_rate_percent', $line) && $line['gst_rate_percent'] === '') {
                $line['gst_rate_percent'] = null;
            }

            return $line;
        })->toArray();

        $data = $this->validate($this->rules(), [], [
            'customer_id' => 'customer',
            'invoice_date' => 'invoice date',
            'lines.*.product_id' => 'product',
            'lines.*.qty_bulk' => 'bulk quantity',
            'lines.*.pack_size_id' => 'pack size',
            'lines.*.pack_count' => 'pack count',
            'lines.*.rate_per_kg' => 'rate per kg',
            'lines.*.gst_rate_percent' => 'GST rate',
        ]);

        $productIds = collect($data['lines'])->pluck('product_id')->filter()->unique();
        if ($productIds->isNotEmpty()) {
            $productMap = Product::whereIn('id', $productIds)->get(['id', 'can_sell'])->keyBy('id');
            foreach ($data['lines'] as $index => $line) {
                $product = $productMap->get((int) $line['product_id']);
                if (! $product || ! $product->can_sell) {
                    $this->addError('lines.'.$index.'.product_id', 'Selected product cannot be sold.');
                    return;
                }
            }
        }

        if (empty($data['lines'])) {
            $this->addError('form', 'Add at least one invoice line.');
            return;
        }

        $seenDispatchLines = [];
        foreach ($data['lines'] as $index => $line) {
            if (($line['sale_mode'] ?? '') === SalesInvoiceLine::MODE_BULK && empty($line['qty_bulk'])) {
                $this->addError('lines.'.$index.'.qty_bulk', 'Bulk quantity is required.');
                return;
            }

            if (($line['sale_mode'] ?? '') === SalesInvoiceLine::MODE_PACK) {
                if (empty($line['pack_size_id'])) {
                    $this->addError('lines.'.$index.'.pack_size_id', 'Pack size is required for pack mode.');
                    return;
                }
                if (empty($line['pack_count']) || $line['pack_count'] <= 0) {
                    $this->addError('lines.'.$index.'.pack_count', 'Pack count must be greater than zero.');
                    return;
                }

                $pack = $this->packSizeLookup[$line['pack_size_id']] ?? null;
                if (! $pack || (int) $pack['product_id'] !== (int) $line['product_id']) {
                    $this->addError('lines.'.$index.'.pack_size_id', 'Pack size must belong to the product.');
                    return;
                }
            }

            if (isset($line['dispatch_line_id']) && $line['dispatch_line_id']) {
                if (in_array($line['dispatch_line_id'], $seenDispatchLines, true)) {
                    $this->addError('lines.'.$index.'.dispatch_line_id', 'Duplicate dispatch line selected.');
                    return;
                }
                $seenDispatchLines[] = $line['dispatch_line_id'];
            }
        }

        $payload = [
            'customer_id' => $data['customer_id'],
            'invoice_date' => $data['invoice_date'],
            'remarks' => isset($data['remarks']) && trim((string) $data['remarks']) !== '' ? trim($data['remarks']) : null,
            'status' => $this->status,
        ];

        try {
            if ($this->invoiceId) {
                $invoice = SalesInvoice::findOrFail($this->invoiceId);
                $invoice = $invoiceService->update($invoice, $payload, $data['lines']);
            } else {
                $invoice = $invoiceService->create($payload, $data['lines']);
                $this->invoiceId = $invoice->id;
            }

            if ($this->status === SalesInvoice::STATUS_POSTED) {
                $invoiceService->post($invoice);
            }

            session()->flash('success', 'Invoice saved.');
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
            return;
        }

        return redirect()->route('sales-invoices.view');
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
            $this->lines[$index]['computed_total_qty'] = (float) ($this->lines[$index]['qty_bulk'] ?? 0);
        }
    }

    private function recalculateLine(int $index): void
    {
        if (! isset($this->lines[$index])) {
            return;
        }

        $line = $this->lines[$index];
        $mode = $line['sale_mode'] ?? SalesInvoiceLine::MODE_BULK;

        if ($mode === SalesInvoiceLine::MODE_PACK) {
            $this->refreshPackTotals($index);
        } else {
            $this->lines[$index]['computed_total_qty'] = (float) ($line['qty_bulk'] ?? 0);
        }

        $qtyUsed = (float) ($this->lines[$index]['computed_total_qty'] ?? 0);
        $rate = (float) ($this->lines[$index]['rate_per_kg'] ?? 0);
        $gstRate = (float) ($this->lines[$index]['gst_rate_percent'] ?? 0);

        $taxable = round($qtyUsed * $rate, 2);
        $gstAmount = round($taxable * $gstRate / 100, 2);

        $this->lines[$index]['taxable_amount'] = $taxable;
        $this->lines[$index]['gst_amount'] = $gstAmount;
        $this->lines[$index]['line_total'] = round($taxable + $gstAmount, 2);
    }

    private function refreshTotals(): void
    {
        $this->subtotal = round(collect($this->lines)->sum('taxable_amount'), 2);
        $this->total_gst = round(collect($this->lines)->sum('gst_amount'), 2);
        $this->grand_total = round($this->subtotal + $this->total_gst, 2);
    }

    private function loadDispatchOptions(): void
    {
        if (! $this->customer_id) {
            $this->dispatchOptions = [];
            return;
        }

        $this->dispatchOptions = Dispatch::posted()
            ->whereHas('lines', fn ($q) => $q->where('customer_id', $this->customer_id))
            ->orderByDesc('dispatch_date')
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id', 'dispatch_no', 'dispatch_date'])
            ->map(function ($dispatch) {
                $date = $dispatch->dispatch_date ? $dispatch->dispatch_date->toDateString() : '';
                return [
                    'id' => $dispatch->id,
                    'label' => trim($dispatch->dispatch_no.' '.$date),
                ];
            })->toArray();
    }

    private function refreshInvoiceNoPreview(): void
    {
        if ($this->invoiceId) {
            $this->invoiceNoPreview = null;
            return;
        }

        $service = app(SalesInvoiceService::class);
        $this->invoiceNoPreview = $service->generateInvoiceNo($this->invoice_date ?? now()->toDateString());
    }

    private function blankLine(): array
    {
        return [
            'product_id' => '',
            'sale_mode' => SalesInvoiceLine::MODE_BULK,
            'qty_bulk' => null,
            'pack_size_id' => '',
            'pack_count' => null,
            'computed_total_qty' => 0,
            'rate_per_kg' => null,
            'gst_rate_percent' => 0,
            'dispatch_id' => null,
            'dispatch_line_id' => null,
            'uom' => null,
            'taxable_amount' => 0,
            'gst_amount' => 0,
            'line_total' => 0,
            'source_dispatch_qty' => null,
            'source_dispatch_pack_count' => null,
        ];
    }

    private function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.sale_mode' => ['required', Rule::in([SalesInvoiceLine::MODE_BULK, SalesInvoiceLine::MODE_PACK])],
            'lines.*.qty_bulk' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:20'],
            'lines.*.pack_size_id' => ['nullable', 'exists:pack_sizes,id'],
            'lines.*.pack_count' => ['nullable', 'integer', 'gt:0'],
            'lines.*.rate_per_kg' => ['required', 'numeric', 'gt:0'],
            'lines.*.gst_rate_percent' => ['nullable', Rule::in($this->gstRates)],
            'lines.*.dispatch_line_id' => ['nullable', 'integer'],
        ];
    }
}
