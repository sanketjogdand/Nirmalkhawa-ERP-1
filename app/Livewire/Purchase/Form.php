<?php

namespace App\Livewire\Purchase;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\Supplier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Purchase';

    public ?int $purchaseId = null;
    public bool $isLocked = false;

    public $supplier_id;
    public $purchase_date;
    public $supplier_bill_no;
    public $supplier_bill_date;
    public $remarks;

    public float $subtotal = 0;
    public float $total_gst = 0;
    public float $grand_total = 0;

    public array $lines = [];
    public array $gstRates = [0, 5, 18];

    public $suppliers;
    public $products;

    public function mount($purchase = null): void
    {
        $this->suppliers = Supplier::orderBy('name')->get();
        $this->products = Product::where('can_purchase', true)->orderBy('name')->get();

        if ($purchase) {
            $record = Purchase::with('lines')->findOrFail($purchase);
            $this->authorize('purchase.update');

            $this->purchaseId = $record->id;
            $this->supplier_id = $record->supplier_id;
            $this->purchase_date = $record->purchase_date?->toDateString();
            $this->supplier_bill_no = $record->supplier_bill_no;
            $this->supplier_bill_date = $record->supplier_bill_date?->toDateString();
            $this->remarks = $record->remarks;
            $this->isLocked = (bool) $record->is_locked;
            $this->subtotal = (float) $record->subtotal;
            $this->total_gst = (float) $record->total_gst;
            $this->grand_total = (float) $record->grand_total;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'qty' => (float) $line->qty,
                    'uom' => $line->uom,
                    'rate' => (float) $line->rate,
                    'gst_rate_percent' => (float) $line->gst_rate_percent,
                    'taxable_amount' => (float) $line->taxable_amount,
                    'gst_amount' => (float) $line->gst_amount,
                    'line_total' => (float) $line->line_total,
                ];
            })->toArray();
        } else {
            $this->authorize('purchase.create');
            $this->purchase_date = now()->toDateString();
            $this->lines = [
                $this->blankLine(),
            ];
        }

        $this->refreshTotals();
    }

    public function updated($name, $value): void
    {
        if (str_starts_with($name, 'lines.')) {
            $this->updatedLines($value, $name);
        }
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

        if ($field === 'product_id') {
            $this->applyProductDefaults($index);
        }

        $this->recalculateLine($index);
        $this->refreshTotals();
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
        $this->refreshTotals();
    }

    public function save()
    {
        $this->authorize($this->purchaseId ? 'purchase.update' : 'purchase.create');

        if ($this->isLocked) {
            abort(403, 'Purchase is locked and cannot be edited.');
        }

        $validated = $this->validate($this->rules(), [], $this->attributes());
        $computedLines = [];
        $subtotal = 0;
        $totalGst = 0;

        foreach ($validated['lines'] as $line) {
            $product = $this->products->firstWhere('id', (int) $line['product_id']);
            $uom = $line['uom'] ?? $product?->uom;
            $qty = (float) $line['qty'];
            $rate = (float) $line['rate'];
            $gstRate = (float) $line['gst_rate_percent'];
            $taxable = round($qty * $rate, 2);
            $gstAmount = round($taxable * $gstRate / 100, 2);
            $lineTotal = round($taxable + $gstAmount, 2);

            $subtotal += $taxable;
            $totalGst += $gstAmount;

            $computedLines[] = [
                'product_id' => $line['product_id'],
                'description' => $line['description'] ?? null,
                'qty' => $qty,
                'uom' => $uom,
                'rate' => $rate,
                'gst_rate_percent' => $gstRate,
                'taxable_amount' => $taxable,
                'gst_amount' => $gstAmount,
                'line_total' => $lineTotal,
            ];
        }

        $grandTotal = $subtotal + $totalGst;

        $isNew = ! $this->purchaseId;

        DB::transaction(function () use ($validated, $computedLines, $subtotal, $totalGst, $grandTotal) {
            $payload = [
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'supplier_bill_no' => $validated['supplier_bill_no'] ?? null,
                'supplier_bill_date' => $validated['supplier_bill_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'subtotal' => $subtotal,
                'total_gst' => $totalGst,
                'grand_total' => $grandTotal,
            ];

            if ($this->purchaseId) {
                $purchase = Purchase::findOrFail($this->purchaseId);
                if ($purchase->is_locked) {
                    abort(403, 'Purchase is locked and cannot be edited.');
                }

                $purchase->update($payload);
                $purchase->lines()->delete();
                $purchase->lines()->createMany($computedLines);
                $this->purchaseId = $purchase->id;
            } else {
                $payload['created_by'] = Auth::id();
                $purchase = Purchase::create($payload);
                $purchase->lines()->createMany($computedLines);
                $this->purchaseId = $purchase->id;
            }
        });

        session()->flash('success', $isNew ? 'Purchase created.' : 'Purchase saved.');

        return redirect()->route('purchases.view');
    }

    public function render()
    {
        return view('livewire.purchase.form')->with(['title_name' => $this->title ?? 'Purchase']);
    }

    private function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'supplier_bill_no' => ['nullable', 'string', 'max:120'],
            'supplier_bill_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'id')->where('can_purchase', true)],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:30'],
            'lines.*.rate' => ['required', 'numeric', 'gte:0'],
            'lines.*.gst_rate_percent' => ['required', 'numeric', Rule::in($this->gstRates)],
        ];
    }

    private function attributes(): array
    {
        return [
            'supplier_id' => 'Supplier',
            'purchase_date' => 'Purchase date',
            'supplier_bill_no' => 'Supplier bill no',
            'supplier_bill_date' => 'Supplier bill date',
            'lines.*.product_id' => 'Product',
            'lines.*.qty' => 'Quantity',
            'lines.*.rate' => 'Rate',
            'lines.*.gst_rate_percent' => 'GST %',
        ];
    }

    private function blankLine(): array
    {
        return [
            'product_id' => '',
            'description' => '',
            'qty' => null,
            'uom' => null,
            'rate' => null,
            'gst_rate_percent' => 0,
            'taxable_amount' => 0,
            'gst_amount' => 0,
            'line_total' => 0,
        ];
    }

    private function applyProductDefaults(int $index): void
    {
        $productId = $this->lines[$index]['product_id'] ?? null;
        $product = $this->products->firstWhere('id', (int) $productId);

        if ($product) {
            $this->lines[$index]['uom'] = $product->uom;
            if (! isset($this->lines[$index]['gst_rate_percent']) || $this->lines[$index]['gst_rate_percent'] === null) {
                $rate = (float) ($product->default_gst_rate ?? 0);
                $this->lines[$index]['gst_rate_percent'] = in_array($rate, $this->gstRates, true) ? $rate : 0;
            }
        }
    }

    private function recalculateLine(int $index): void
    {
        $line = $this->lines[$index];
        $qty = (float) ($line['qty'] ?? 0);
        $rate = (float) ($line['rate'] ?? 0);
        $gstRate = (float) ($line['gst_rate_percent'] ?? 0);

        $taxable = round($qty * $rate, 2);
        $gstAmount = round($taxable * $gstRate / 100, 2);
        $lineTotal = round($taxable + $gstAmount, 2);

        $this->lines[$index]['taxable_amount'] = $taxable;
        $this->lines[$index]['gst_amount'] = $gstAmount;
        $this->lines[$index]['line_total'] = $lineTotal;
    }

    private function refreshTotals(): void
    {
        $this->subtotal = 0;
        $this->total_gst = 0;

        foreach ($this->lines as $line) {
            $this->subtotal += (float) ($line['taxable_amount'] ?? 0);
            $this->total_gst += (float) ($line['gst_amount'] ?? 0);
        }

        $this->grand_total = $this->subtotal + $this->total_gst;
    }
}
