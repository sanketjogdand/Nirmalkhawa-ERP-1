<?php

namespace App\Livewire\MaterialConsumption;

use App\Models\MaterialConsumption;
use App\Models\Product;
use App\Services\InventoryService;
use App\Services\MaterialConsumptionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Material Consumption';

    public ?int $consumptionId = null;
    public bool $isLocked = false;

    public $consumption_date;
    public $consumption_type;
    public $remarks;

    public array $lines = [];

    public $products;
    public array $consumptionTypes = [];

    public function mount($materialConsumption = null): void
    {
        $this->products = Product::where('can_consume', true)
            ->where('can_stock', true)
            ->orderBy('name')
            ->get();
        $this->consumptionTypes = config('material_consumption.types', []);

        if ($materialConsumption) {
            $record = MaterialConsumption::with(['lines.product'])->findOrFail($materialConsumption);
            $this->authorize('materialconsumption.update');

            $this->consumptionId = $record->id;
            $this->consumption_date = $record->consumption_date?->toDateString();
            $this->consumption_type = $record->consumption_type;
            $this->remarks = $record->remarks;
            $this->isLocked = (bool) $record->is_locked;

            $this->lines = $record->lines->map(function ($line) {
                return [
                    'product_id' => $line->product_id,
                    'qty' => (float) $line->qty,
                    'uom' => $line->uom,
                    'remarks' => $line->remarks,
                ];
            })->toArray();
        } else {
            $this->authorize('materialconsumption.create');
            $this->consumption_date = now()->toDateString();
            $this->consumption_type = array_key_first($this->consumptionTypes) ?: '';
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

    public function save()
    {
        $this->authorize($this->consumptionId ? 'materialconsumption.update' : 'materialconsumption.create');

        if ($this->isLocked) {
            abort(403, 'Locked records cannot be edited.');
        }

        $validated = $this->validate($this->rules(), [], $this->attributes());

        $products = $this->products->keyBy('id');
        $lines = collect($validated['lines'])
            ->map(function ($line) use ($products) {
                $product = $products[(int) $line['product_id']] ?? null;
                $uom = $line['uom'] ?: $product?->uom;

                return [
                    'product_id' => (int) $line['product_id'],
                    'qty' => (float) $line['qty'],
                    'uom' => $uom,
                    'remarks' => $line['remarks'] ?? null,
                ];
            })
            ->values()
            ->all();

        $payload = [
            'consumption_date' => $validated['consumption_date'],
            'consumption_type' => $validated['consumption_type'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        $service = app(MaterialConsumptionService::class);
        $inventoryService = app(InventoryService::class);
        $isNew = ! $this->consumptionId;

        try {
            if ($this->consumptionId) {
                $record = MaterialConsumption::findOrFail($this->consumptionId);
                $service->update($record, $payload, $lines, $inventoryService);
            } else {
                $payload['created_by'] = Auth::id();
                $service->create($payload, $lines, $inventoryService);
            }
        } catch (RuntimeException $e) {
            $this->addError('lines', $e->getMessage());
            return;
        }

        session()->flash('success', $isNew ? 'Material consumption recorded.' : 'Material consumption saved.');

        return $this->redirect(route('material-consumptions.view'), navigate: true);
    }

    public function render()
    {
        return view('livewire.material-consumption.form')
            ->with(['title_name' => $this->title ?? 'Material Consumption']);
    }

    private function rules(): array
    {
        return [
            'consumption_date' => ['required', 'date'],
            'consumption_type' => ['required', Rule::in(array_keys($this->consumptionTypes))],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('can_consume', true)->where('can_stock', true),
            ],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.uom' => ['nullable', 'string', 'max:30'],
            'lines.*.remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function attributes(): array
    {
        return [
            'consumption_date' => 'Consumption date',
            'consumption_type' => 'Consumption type',
            'lines.*.product_id' => 'Product',
            'lines.*.qty' => 'Quantity',
        ];
    }

    private function blankLine(): array
    {
        return [
            'product_id' => '',
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
