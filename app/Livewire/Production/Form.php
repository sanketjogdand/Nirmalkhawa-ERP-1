<?php

namespace App\Livewire\Production;

use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\Recipe;
use App\Services\InventoryService;
use App\Services\ProductionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Production';

    public ?int $productionId = null;
    public $date;
    public $output_product_id;
    public $recipe_id;
    public $actual_output_qty;
    public $output_uom;
    public $remarks;

    public array $inputs = [];
    public array $stockWarnings = [];

    public $outputProducts;
    public $consumableProducts;
    public $recipes;

    public bool $isLocked = false;

    public function mount($production = null): void
    {
        $this->outputProducts = Product::orderBy('name')->get();
        $this->consumableProducts = Product::where('can_consume', true)->orderBy('name')->get();

        if ($production) {
            $record = ProductionBatch::with(['inputs', 'recipe', 'outputProduct'])->findOrFail($production);
            $this->authorize('production.update');

            $this->productionId = $record->id;
            $this->isLocked = $record->is_locked;
            $this->fill($record->only(['date', 'output_product_id', 'recipe_id', 'actual_output_qty', 'output_uom', 'remarks']));
            $this->date = $record->date ? $record->date->toDateString() : null;
            $this->inputs = $record->inputs->map(function ($input) {
                return [
                    'recipe_item_id' => $input->recipe_item_id,
                    'material_product_id' => $input->material_product_id,
                    'planned_qty' => (float) $input->planned_qty,
                    'actual_qty_used' => $input->actual_qty_used !== null ? (float) $input->actual_qty_used : null,
                    'uom' => $input->uom,
                    'is_yield_base' => (bool) $input->is_yield_base,
                ];
            })->toArray();
        } else {
            $this->authorize('production.create');
            $this->date = now()->toDateString();
            $this->actual_output_qty = null;
        }

        if ($this->output_product_id) {
            $this->refreshRecipes();
        }

        if (! $this->productionId && $this->recipe_id) {
            $this->loadRecipeItems($this->recipe_id);
        }

        if (! $this->output_uom && $this->output_product_id) {
            $product = $this->outputProducts->firstWhere('id', (int) $this->output_product_id);
            if ($product) {
                $this->output_uom = $product->uom;
            }
        }
    }

    public function updatedOutputProductId($value): void
    {
        $this->recipe_id = null;
        $this->inputs = [];
        $this->stockWarnings = [];

        if (! $value) {
            return;
        }

        $product = $this->outputProducts->firstWhere('id', (int) $value);
        $this->output_uom = $product->uom ?? null;
        $this->refreshRecipes();

        $activeRecipe = $this->recipes->firstWhere('is_active', true);
        if ($activeRecipe) {
            $this->recipe_id = $activeRecipe->id;
            $this->loadRecipeItems($activeRecipe->id);
        }
    }

    public function updatedRecipeId($value): void
    {
        if ($value) {
            $this->loadRecipeItems($value);
        }
    }

    public function checkStocks(InventoryService $inventoryService): void
    {
        $this->stockWarnings = [];

        $products = Product::whereIn('id', collect($this->inputs)->pluck('material_product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        foreach ($this->inputs as $index => $input) {
            if (empty($input['material_product_id']) || ! isset($input['actual_qty_used'])) {
                continue;
            }

            $available = $inventoryService->getOnHand((int) $input['material_product_id']);
            $needed = (float) $input['actual_qty_used'];
            if ($needed > $available) {
                $productName = $products[$input['material_product_id']]->name ?? ('Product ID '.$input['material_product_id']);
                $this->stockWarnings[] = "Short on {$productName}: need {$needed}, available {$available}.";
            }
        }

        if (empty($this->stockWarnings)) {
            session()->flash('info', 'All inputs appear to have sufficient stock right now.');
        }
    }

    public function save(ProductionService $productionService, InventoryService $inventoryService)
    {
        $this->authorize($this->productionId ? 'production.update' : 'production.create');

        if ($this->isLocked) {
            $this->addError('form', 'Locked batches cannot be edited.');
            return;
        }

        $this->syncInputsToRecipe();

        $data = $this->validate($this->rules());

        $materialIds = collect($this->inputs)->pluck('material_product_id')->filter()->unique();
        if ($materialIds->isNotEmpty()) {
            $materialMap = Product::whereIn('id', $materialIds)->get(['id', 'can_consume'])->keyBy('id');
            foreach ($this->inputs as $index => $input) {
                if (! $input['material_product_id']) {
                    continue;
                }
                $product = $materialMap->get((int) $input['material_product_id']);
                if (! $product || ! $product->can_consume) {
                    $this->addError('inputs.'.$index.'.material_product_id', 'Selected product cannot be consumed.');
                    return;
                }
            }
        }

        if (! collect($this->inputs)->contains(fn ($item) => $item['is_yield_base'] ?? false)) {
            $this->addError('inputs', 'Select one input as the yield base.');
            return;
        }

        $recipeBelongs = Recipe::where('id', $this->recipe_id)
            ->where('output_product_id', $this->output_product_id)
            ->exists();

        if (! $recipeBelongs) {
            $this->addError('recipe_id', 'Selected recipe does not match the chosen output product.');
            return;
        }

        $payload = [
            'date' => $data['date'],
            'output_product_id' => (int) $data['output_product_id'],
            'recipe_id' => (int) $data['recipe_id'],
            'actual_output_qty' => (float) $data['actual_output_qty'],
            'output_uom' => $data['output_uom'],
            'remarks' => $data['remarks'] ?? null,
        ];

        try {
            if ($this->productionId) {
                $batch = ProductionBatch::findOrFail($this->productionId);
                $productionService->update($batch, $payload, $this->inputs, $inventoryService);
                session()->flash('success', 'Production updated.');
            } else {
                $productionService->create($payload, $this->inputs, $inventoryService);
                session()->flash('success', 'Production saved.');
            }
        } catch (RuntimeException $e) {
            session()->flash('danger', $e->getMessage());
            return;
        }

        return redirect()->route('productions.view');
    }

    public function setYieldBase(int $index): void
    {
        foreach ($this->inputs as $i => $input) {
            $this->inputs[$i]['is_yield_base'] = $i === $index;
        }
    }

    public function getYieldPreviewProperty(): ?array
    {
        $yieldInput = collect($this->inputs)->firstWhere('is_yield_base', true);
        if (! $yieldInput || empty($yieldInput['actual_qty_used']) || empty($this->actual_output_qty)) {
            return null;
        }

        $baseQty = (float) $yieldInput['actual_qty_used'];
        if ($baseQty <= 0) {
            return null;
        }

        $ratio = round((float) $this->actual_output_qty / $baseQty, 4);

        return [
            'ratio' => $ratio,
            'pct' => round($ratio * 100, 2),
        ];
    }

    public function render()
    {
        return view('livewire.production.form')
            ->with(['title_name' => $this->title ?? 'Production']);
    }

    private function refreshRecipes(): void
    {
        $this->recipes = Recipe::where('output_product_id', $this->output_product_id)
            ->orderByDesc('version')
            ->get();
    }

    private function loadRecipeItems(int $recipeId): void
    {
        $recipe = Recipe::with('items')->find($recipeId);
        if (! $recipe) {
            return;
        }

        $this->recipe_id = $recipeId;
        $this->stockWarnings = [];
        $this->inputs = $recipe->items->map(function ($item) {
            return [
                'recipe_item_id' => $item->id,
                'material_product_id' => $item->material_product_id,
                'planned_qty' => (float) $item->standard_qty,
                'actual_qty_used' => null,
                'uom' => $item->uom,
                'is_yield_base' => (bool) $item->is_yield_base,
            ];
        })->toArray();

        if (! $this->output_uom && $recipe->output_uom) {
            $this->output_uom = $recipe->output_uom;
        }

        $this->recalculateActualsFromOutput();
    }

    private function syncInputsToRecipe(): void
    {
        if (! $this->recipe_id) {
            return;
        }

        $recipe = Recipe::with('items')->find($this->recipe_id);
        if (! $recipe) {
            return;
        }

        $existing = collect($this->inputs)->keyBy('recipe_item_id');

        $this->inputs = $recipe->items->map(function ($item) use ($existing) {
            $current = $existing->get($item->id, []);

            return [
                'recipe_item_id' => $item->id,
                'material_product_id' => $item->material_product_id,
                'planned_qty' => (float) $item->standard_qty,
                'actual_qty_used' => array_key_exists('actual_qty_used', $current) ? (float) $current['actual_qty_used'] : null,
                'uom' => $item->uom,
                'is_yield_base' => array_key_exists('is_yield_base', $current) ? (bool) $current['is_yield_base'] : (bool) $item->is_yield_base,
            ];
        })->toArray();
    }

    private function recalculateActualsFromOutput(): void
    {
        if (! $this->actual_output_qty || empty($this->inputs)) {
            return;
        }

        $outputQty = (float) $this->actual_output_qty;

        foreach ($this->inputs as $index => $input) {
            $plannedPerUnit = isset($input['planned_qty']) ? (float) $input['planned_qty'] : 0;
            $this->inputs[$index]['actual_qty_used'] = round($plannedPerUnit * $outputQty, 3);
        }
    }

    private function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'output_product_id' => ['required', 'exists:products,id'],
            'recipe_id' => ['required', 'exists:recipes,id'],
            'actual_output_qty' => ['required', 'numeric', 'gt:0'],
            'output_uom' => ['required', 'string', 'max:20'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'inputs' => ['required', 'array', 'min:1'],
            'inputs.*.material_product_id' => ['required', 'exists:products,id'],
            'inputs.*.planned_qty' => ['nullable', 'numeric', 'gte:0'],
            'inputs.*.actual_qty_used' => ['required', 'numeric', 'gt:0'],
            'inputs.*.uom' => ['required', 'string', 'max:20'],
            'inputs.*.is_yield_base' => ['required', 'boolean'],
            'inputs.*.recipe_item_id' => ['nullable', Rule::exists('recipe_items', 'id')],
        ];
    }
}
