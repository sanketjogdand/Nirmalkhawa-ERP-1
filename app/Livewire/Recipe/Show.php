<?php

namespace App\Livewire\Recipe;

use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public $title = 'Recipes';
    public Recipe $recipe;

    public function mount(Recipe $recipe): void
    {
        $this->authorize('recipe.view');
        $this->recipe = $recipe->load(['outputProduct', 'items.materialProduct']);
    }

    public function render()
    {
        return view('livewire.recipe.show')
            ->with(['title_name' => $this->title ?? 'Recipes']);
    }
}
