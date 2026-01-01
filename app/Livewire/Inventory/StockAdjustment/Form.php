<?php

namespace App\Livewire\Inventory\StockAdjustment;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Services\InventoryService;
use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Stock Adjustment';
    public ?int $adjustmentId = null;
    public bool $isLocked = false;

    public $adjustment_date;
    public $reason;
    public $remarks;
    public array $lines = [];

    public array $reasons = [];
    public $products;

    public function mount($stockAdjustment = null): void
    {
        $this->products = Product::where('can_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $this->reasons = StockAdjustment::REASONS;

        if ($stockAdjustment) {
            $record = StockAdjustment::with(['lines.product'])->findOrFail($stockAdjustment);
            $this->authorize('stockadjustment.update');

            $this->adjustmentId = $record->id;
            $this->adjustment_date = $record->adjustment_date?->toDateString();
            $this->reason = $record->reason;
            $this->remarks = $record->remarks;
            $this->isLocked = (bool) $record->is_locked;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'direction' => $line->direction,
                    'qty' => (float) $line->qty,
                    'uom' => $line->uom,
                    'remarks' => $line->remarks,
                ];
            })->toArray();
        } else {
            $this->authorize('stockadjustment.create');
            $this->adjustment_date = now()->toDateString();
            $this->reason = $this->reasons[0] ?? '';
            $this->lines = [$this->blankLine()];
        }
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

    public function save(InventoryService $inventoryService, StockAdjustmentService $service)
    {
        $this->authorize($this->adjustmentId ? 'stockadjustment.update' : 'stockadjustment.create');

        if ($this->isLocked) {
            abort(403, 'Locked adjustments cannot be edited.');
        }

        $validated = $this->validate($this->rules(), [], $this->attributes());
        $products = $this->products->keyBy('id');

        $lines = collect($validated['lines'])
            ->map(function ($line) use ($products) {
                $product = $products[(int) $line['product_id']] ?? null;
                $uom = $line['uom'] ?: $product?->uom;

                return [
                    'product_id' => (int) $line['product_id'],
                    'direction' => $line['direction'],
                    'qty' => (float) $line['qty'],
                    'uom' => $uom,
                    'remarks' => $line['remarks'] ?? null,
                ];
            })
            ->values()
            ->all();

        $payload = [
            'adjustment_date' => $validated['adjustment_date'],
            'reason' => $validated['reason'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        $isNew = ! $this->adjustmentId;

        try {
            if ($this->adjustmentId) {
                $record = StockAdjustment::findOrFail($this->adjustmentId);
                $service->update($record, $payload, $lines, $inventoryService);
            } else {
                $payload['created_by'] = Auth::id();
                $service->create($payload, $lines, $inventoryService);
            }
        } catch (RuntimeException $e) {
            $this->addError('lines', $e->getMessage());
            return;
        }

        session()->flash('success', $isNew ? 'Stock adjustment created.' : 'Stock adjustment saved.');

        return $this->redirect(route('inventory.stock-adjustments'), navigate: true);
    }

    public function render()
    {
        return view('livewire.inventory.stock-adjustment.form')
            ->with(['title_name' => $this->title ?? 'Stock Adjustment']);
    }

    private function rules(): array
    {
        return [
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', Rule::in($this->reasons)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('can_stock', true)->where('is_active', true),
            ],
            'lines.*.direction' => ['required', Rule::in([StockAdjustmentLine::DIRECTION_IN, StockAdjustmentLine::DIRECTION_OUT])],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:30'],
            'lines.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function attributes(): array
    {
        return [
            'adjustment_date' => 'Adjustment date',
            'reason' => 'Reason',
            'lines.*.product_id' => 'Product',
            'lines.*.direction' => 'Direction',
            'lines.*.qty' => 'Quantity',
        ];
    }

    private function blankLine(): array
    {
        return [
            'product_id' => '',
            'direction' => StockAdjustmentLine::DIRECTION_IN,
            'qty' => null,
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
}
