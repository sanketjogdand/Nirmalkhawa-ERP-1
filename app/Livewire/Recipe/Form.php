<?php

namespace App\Livewire\Recipe;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\Uom;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Recipes';
    public bool $canOverrideOutputProduct = false;

    public ?int $recipeId = null;
    public $output_product_id;
    public ?int $originalOutputProductId = null;
    public $outputProductSearch = '';
    public $name;
    public $version = 1;
    public $is_active = true;
    public $notes;
    public $output_qty = 1;
    public $output_uom;
    public $materialSearch = '';
    public array $items = [];
    public Collection $uoms;

    public function mount($recipe = null): void
    {
        $this->canOverrideOutputProduct = auth()->user()?->can('recipe.override_output_product') ?? false;
        $this->uoms = Uom::orderBy('name')->get();

        if ($recipe) {
            $record = Recipe::with(['items', 'outputProduct'])->findOrFail($recipe);
            $this->authorize('recipe.update');
            $this->recipeId = $record->id;
            $this->fill($record->only([
                'output_product_id',
                'name',
                'version',
                'is_active',
                'notes',
                'output_qty',
                'output_uom',
            ]));
            $this->outputProductSearch = optional($record->outputProduct)->name ?? '';
            $this->items = $record->items
                ->sortBy('id')
                ->map(function ($item) {
                    return [
                        'material_product_id' => $item->material_product_id,
                        'standard_qty' => $item->standard_qty,
                        'uom' => $item->uom,
                        'is_yield_base' => (bool) $item->is_yield_base,
                    ];
                })->values()->toArray();
        } else {
            $this->authorize('recipe.create');
            $this->items = $this->items ?: [
                [
                    'material_product_id' => null,
                    'standard_qty' => null,
                    'uom' => $this->defaultUom(),
                    'is_yield_base' => false,
                ],
            ];
        }

        $this->originalOutputProductId = $this->output_product_id;

        if (! $this->output_uom) {
            $this->output_uom = $this->defaultUom();
        }
    }

    public function updatedOutputProductId($value): void
    {
        if (! $value) {
            return;
        }

        $product = Product::find($value);

        if (! $product) {
            return;
        }

        if (! $this->canUseProductForOutput($product)) {
            $this->addError('output_product_id', 'You need permission to select a non-producible product.');
            $this->output_product_id = null;
            return;
        }

        $this->outputProductSearch = $product->name;

        if (! $this->recipeId || $product->id !== $this->originalOutputProductId) {
            $this->version = $this->nextVersionForProduct($product->id);
        }

        if (! $this->output_uom) {
            $this->output_uom = $product->uom;
        }
    }

    public function updated($field, $value): void
    {
        if (preg_match('/^items\.(\d+)\.material_product_id$/', $field, $matches)) {
            $index = (int) $matches[1];
            $product = Product::find($value);
            if ($product) {
                $this->items[$index]['uom'] = $product->uom;
            }
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'material_product_id' => null,
            'standard_qty' => null,
            'uom' => $this->defaultUom(),
            'is_yield_base' => false,
        ];
    }

    public function removeItem($index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function selectOutputProduct(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        if (! $this->canUseProductForOutput($product)) {
            $this->addError('output_product_id', 'You need permission to select a non-producible product.');
            return;
        }

        $this->output_product_id = $product->id;
        $this->outputProductSearch = $product->name;

        if (! $this->recipeId || $product->id !== $this->originalOutputProductId) {
            $this->version = $this->nextVersionForProduct($product->id);
        }

        if (! $this->output_uom) {
            $this->output_uom = $product->uom;
        }
    }

    public function save()
    {
        $this->authorize($this->recipeId ? 'recipe.update' : 'recipe.create');

        if ($this->output_product_id && (! $this->recipeId || $this->output_product_id !== $this->originalOutputProductId)) {
            $this->version = $this->nextVersionForProduct($this->output_product_id);
        }

        $data = $this->validate($this->rules(), [], [
            'output_product_id' => 'output product',
            'output_qty' => 'batch quantity',
            'output_uom' => 'batch UOM',
            'items' => 'recipe items',
            'items.*.material_product_id' => 'material product',
            'items.*.standard_qty' => 'material quantity',
            'items.*.uom' => 'material UOM',
            'items.*.is_yield_base' => 'yield base flag',
        ]);

        $data['is_active'] = (bool) $data['is_active'];

        $yieldBaseCount = collect($data['items'])->where('is_yield_base', true)->count();
        if ($yieldBaseCount > 1) {
            session()->flash('warning', 'Multiple yield base items selected. Only one is recommended.');
        }

        $outputProduct = Product::find($data['output_product_id']);
        if (! $outputProduct) {
            $this->addError('output_product_id', 'Output product not found.');
            return;
        }

        if (! $this->canUseProductForOutput($outputProduct)) {
            $this->addError('output_product_id', 'You need permission to select a non-producible product.');
            return;
        }

        DB::transaction(function () use ($data) {
            $recipe = $this->recipeId
                ? Recipe::findOrFail($this->recipeId)
                : new Recipe();

            $recipe->fill([
                'output_product_id' => $data['output_product_id'],
                'name' => $data['name'],
                'version' => $data['version'],
                'is_active' => $data['is_active'],
                'notes' => $data['notes'] ?? null,
                'output_qty' => $data['output_qty'],
                'output_uom' => $data['output_uom'],
            ]);

            $recipe->save();

            $recipe->items()->delete();
            foreach ($data['items'] as $item) {
                $recipe->items()->create([
                    'material_product_id' => $item['material_product_id'],
                    'standard_qty' => $item['standard_qty'],
                    'uom' => $item['uom'],
                    'is_yield_base' => (bool) ($item['is_yield_base'] ?? false),
                ]);
            }

            if ($recipe->is_active) {
                Recipe::where('output_product_id', $recipe->output_product_id)
                    ->where('id', '!=', $recipe->id)
                    ->update(['is_active' => false]);
            }
        });

        session()->flash('success', $this->recipeId ? 'Recipe updated.' : 'Recipe created.');

        return redirect()->route('recipes.view');
    }

    public function render()
    {
        $outputProducts = $this->outputProductOptions();
        $materialProducts = $this->materialProductOptions();

        return view('livewire.recipe.form', compact('outputProducts', 'materialProducts'))
            ->with(['title_name' => $this->title ?? 'Recipes']);
    }

    private function rules(): array
    {
        return [
            'output_product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('recipes', 'version')
                    ->where(fn ($q) => $q->where('output_product_id', $this->output_product_id)->whereNull('deleted_at'))
                    ->ignore($this->recipeId),
            ],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'output_qty' => ['required', 'numeric', 'gt:0'],
            'output_uom' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_product_id' => ['required', 'exists:products,id'],
            'items.*.standard_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.uom' => ['required', 'string', 'max:20'],
            'items.*.is_yield_base' => ['boolean'],
        ];
    }

    private function nextVersionForProduct(int $productId): int
    {
        $latest = Recipe::withTrashed()
            ->where('output_product_id', $productId)
            ->max('version');

        return ($latest ?? 0) + 1;
    }

    private function defaultUom(): ?string
    {
        return $this->uoms->first()->name ?? null;
    }

    private function outputProductOptions()
    {
        $selectedId = $this->output_product_id;

        return Product::query()
            ->when($this->outputProductSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->outputProductSearch.'%')
                        ->orWhere('code', 'like', '%'.$this->outputProductSearch.'%');
                });
            })
            ->when(! $this->canOverrideOutputProduct, function ($q) use ($selectedId) {
                $q->where(function ($inner) use ($selectedId) {
                    $inner->where('can_produce', true);
                    if ($selectedId) {
                        $inner->orWhere('id', $selectedId);
                    }
                });
            })
            ->when($this->canOverrideOutputProduct && $selectedId, fn ($q) => $q->orWhere('id', $selectedId))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'code', 'uom', 'can_produce']);
    }

    private function materialProductOptions()
    {
        $selectedIds = collect($this->items)
            ->pluck('material_product_id')
            ->filter()
            ->values()
            ->all();

        return Product::query()
            ->when($this->materialSearch, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->materialSearch.'%')
                        ->orWhere('code', 'like', '%'.$this->materialSearch.'%');
                });
            })
            ->when(! $this->materialSearch, fn ($q) => $q->where('can_consume', true))
            ->when(! empty($selectedIds), fn ($q) => $q->orWhereIn('id', $selectedIds))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'code', 'uom', 'can_consume']);
    }

    private function canUseProductForOutput(Product $product): bool
    {
        return $product->can_produce || $this->canOverrideOutputProduct || $product->id === $this->originalOutputProductId;
    }
}
