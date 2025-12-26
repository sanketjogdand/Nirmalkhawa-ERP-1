<?php

namespace App\Livewire\Grn;

use App\Models\Grn;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\GrnService;
use App\Services\InventoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Material Received (GRN)';

    public ?int $grnId = null;
    public bool $isLocked = false;

    public $supplier_id;
    public $grn_date;
    public $purchase_id;
    public $remarks;

    public array $lines = [];

    public $suppliers;
    public $products;
    public $purchaseOptions = [];
    public $purchaseLines = [];

    public function mount($grn = null): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->products = Product::where('can_purchase', true)
            ->where('can_stock', true)
            ->orderBy('name')
            ->get();

        if ($grn) {
            $record = Grn::with(['lines', 'purchase', 'supplier'])->findOrFail($grn);
            $this->authorize('grn.update');

            $this->grnId = $record->id;
            $this->supplier_id = $record->supplier_id;
            $this->grn_date = $record->grn_date?->toDateString();
            $this->purchase_id = $record->purchase_id;
            $this->remarks = $record->remarks;
            $this->isLocked = (bool) $record->is_locked;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'received_qty' => (float) $line->received_qty,
                    'uom' => $line->uom,
                    'remarks' => $line->remarks,
                ];
            })->toArray();
        } else {
            $this->authorize('grn.create');
            $this->grn_date = now()->toDateString();
            $this->lines = [$this->blankLine()];
        }

        $this->loadPurchaseOptions();
        $this->loadPurchaseLines();
    }

    public function updatedSupplierId(): void
    {
        $this->loadPurchaseOptions();
        if ($this->purchase_id) {
            $this->purchase_id = null;
            $this->purchaseLines = [];
        }
    }

    public function updatedPurchaseId(): void
    {
        $this->loadPurchaseLines();
    }

    public function updated($name): void
    {
        if (str_starts_with($name, 'lines.') && str_ends_with($name, 'product_id')) {
            $parts = explode('.', $name);
            $index = (int) ($parts[1] ?? 0);
            $this->applyProductDefaults($index);
        }
    }

    public function addLine(): void
    {
        if ($this->isLocked) {
            return;
        }

        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $index): void
    {
        if ($this->isLocked) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save()
    {
        $this->authorize($this->grnId ? 'grn.update' : 'grn.create');

        if ($this->isLocked) {
            abort(403, 'GRN is locked and cannot be edited.');
        }

        $validated = $this->validate($this->rules(), [], $this->attributes());
        if ($validated['purchase_id'] === '') {
            $validated['purchase_id'] = null;
        }

        $products = $this->products->keyBy('id');
        $lines = collect($validated['lines'])
            ->map(function ($line) use ($products) {
                $product = $products[(int) $line['product_id']] ?? null;
                $uom = $line['uom'] ?: $product?->uom;

                return [
                    'product_id' => (int) $line['product_id'],
                    'received_qty' => (float) $line['received_qty'],
                    'uom' => $uom,
                    'remarks' => $line['remarks'] ?? null,
                ];
            })
            ->values()
            ->all();

        $payload = [
            'supplier_id' => $validated['supplier_id'],
            'purchase_id' => $validated['purchase_id'] ?? null,
            'grn_date' => $validated['grn_date'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        $grnService = app(GrnService::class);
        $inventoryService = app(InventoryService::class);
        $isNew = ! $this->grnId;

        if ($this->grnId) {
            $grn = Grn::findOrFail($this->grnId);
            $grnService->update($grn, $payload, $lines, $inventoryService);
        } else {
            $payload['created_by'] = Auth::id();
            $grnService->create($payload, $lines, $inventoryService);
        }

        session()->flash('success', $isNew ? 'GRN created.' : 'GRN saved.');

        return $this->redirect(route('grns.view'), navigate: true);
    }

    public function render()
    {
        $qtyWarnings = $this->purchaseQtyWarnings();

        return view('livewire.grn.form', [
            'purchaseLines' => $this->purchaseLines,
            'qtyWarnings' => $qtyWarnings,
        ])->with(['title_name' => $this->title ?? 'Material Received (GRN)']);
    }

    private function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'grn_date' => ['required', 'date'],
            'purchase_id' => [
                'nullable',
                'integer',
                Rule::exists('purchases', 'id')->where(function ($q) {
                    if ($this->supplier_id) {
                        $q->where('supplier_id', $this->supplier_id);
                    }
                }),
            ],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('can_purchase', true)->where('can_stock', true),
            ],
            'lines.*.received_qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:30'],
            'lines.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function attributes(): array
    {
        return [
            'supplier_id' => 'Supplier',
            'grn_date' => 'GRN date',
            'purchase_id' => 'Purchase bill',
            'lines.*.product_id' => 'Product',
            'lines.*.received_qty' => 'Received Qty',
        ];
    }

    private function blankLine(): array
    {
        return [
            'product_id' => '',
            'received_qty' => null,
            'uom' => null,
            'remarks' => null,
        ];
    }

    private function applyProductDefaults(int $index): void
    {
        $productId = $this->lines[$index]['product_id'] ?? null;
        $product = $this->products->firstWhere('id', (int) $productId);
        if ($product) {
            $this->lines[$index]['uom'] = $product->uom;
        }
    }

    private function loadPurchaseOptions(): void
    {
        if (! $this->supplier_id) {
            $this->purchaseOptions = [];
            return;
        }

        $this->purchaseOptions = Purchase::where('supplier_id', $this->supplier_id)
            ->orderByDesc('purchase_date')
            ->limit(50)
            ->get(['id', 'supplier_bill_no', 'purchase_date'])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'label' => trim(($row->supplier_bill_no ?: 'Bill #'.$row->id).' | '.optional($row->purchase_date)->format('Y-m-d')),
                ];
            })
            ->toArray();
    }

    private function loadPurchaseLines(): void
    {
        $this->purchaseLines = [];
        if (! $this->purchase_id) {
            return;
        }

        $purchase = Purchase::with(['lines.product'])->find($this->purchase_id);
        if (! $purchase || ($this->supplier_id && $purchase->supplier_id !== (int) $this->supplier_id)) {
            return;
        }

        $this->purchaseLines = $purchase->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'product_name' => $line->product?->name,
                'qty' => (float) $line->qty,
                'uom' => $line->uom,
            ];
        })->toArray();
    }

    private function purchaseQtyWarnings(): array
    {
        if (empty($this->purchaseLines)) {
            return [];
        }

        $billed = collect($this->purchaseLines)->mapWithKeys(function ($line) {
            return [$line['product_id'] => (float) $line['qty']];
        });

        $warnings = [];
        foreach ($this->lines as $index => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $received = (float) ($line['received_qty'] ?? 0);
            $billedQty = $billed[$productId] ?? null;
            if ($billedQty !== null && $received > $billedQty) {
                $warnings[$index] = "Received {$received} exceeds billed {$billedQty}.";
            }
        }

        return $warnings;
    }
}
