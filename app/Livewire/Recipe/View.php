<?php

namespace App\Livewire\Recipe;

use App\Models\Product;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Recipes';
    public $perPage = 25;
    public $search = '';
    public $status = '';
    public $outputProductId = '';

    public function mount(): void
    {
        $this->authorize('recipe.view');
    }

    public function updating($field): void
    {
        if (in_array($field, ['search', 'status', 'outputProductId'])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $recipeId): void
    {
        $this->authorize('recipe.activate');

        $recipe = Recipe::findOrFail($recipeId);
        $recipe->is_active = ! $recipe->is_active;
        $recipe->save();

        if ($recipe->is_active) {
            Recipe::where('output_product_id', $recipe->output_product_id)
                ->where('id', '!=', $recipe->id)
                ->update(['is_active' => false]);
        }

        session()->flash('success', $recipe->is_active ? 'Recipe activated.' : 'Recipe deactivated.');
    }

    public function delete(int $recipeId): void
    {
        $this->authorize('recipe.delete');
        $recipe = Recipe::findOrFail($recipeId);
        $recipe->delete();

        session()->flash('success', 'Recipe deleted.');
        $this->resetPage();
    }

    public function duplicate(int $recipeId)
    {
        $this->authorize('recipe.create');

        $recipe = Recipe::with('items')->findOrFail($recipeId);
        $nextVersion = (Recipe::withTrashed()
            ->where('output_product_id', $recipe->output_product_id)
            ->max('version') ?? 0) + 1;

        $copy = Recipe::create([
            'output_product_id' => $recipe->output_product_id,
            'name' => $recipe->name,
            'version' => $nextVersion,
            'is_active' => false,
            'notes' => $recipe->notes,
            'output_qty' => $recipe->output_qty,
            'output_uom' => $recipe->output_uom,
        ]);

        foreach ($recipe->items as $item) {
            $copy->items()->create($item->only([
                'material_product_id',
                'standard_qty',
                'uom',
                'is_yield_base',
            ]));
        }

        session()->flash('success', 'Recipe duplicated as version '.$copy->version.'.');

        return redirect()->route('recipes.edit', $copy->id);
    }

    public function render()
    {
        $recipes = Recipe::with('outputProduct')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('outputProduct', function ($sub) {
                            $sub->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('code', 'like', '%'.$this->search.'%');
                        });
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('is_active', (bool) ((int) $this->status)))
            ->when($this->outputProductId !== '', fn ($q) => $q->where('output_product_id', $this->outputProductId))
            ->orderBy('output_product_id')
            ->orderByDesc('is_active')
            ->orderByDesc('version')
            ->paginate($this->perPage);

        $outputProducts = Product::orderBy('name')->get(['id', 'name', 'code']);

        return view('livewire.recipe.view', compact('recipes', 'outputProducts'))
            ->with(['title_name' => $this->title ?? 'Recipes']);
    }
}
