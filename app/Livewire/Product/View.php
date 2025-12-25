<?php

namespace App\Livewire\Product;

use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class View extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $title = 'Products';
    public $perPage = 25;
    public $search = '';
    public $status = '';
    public $filter_is_packing = '';
    public $filter_can_purchase = '';
    public $filter_can_produce = '';
    public $filter_can_consume = '';
    public $filter_can_sell = '';
    public $filter_can_stock = '';

    public function mount(): void
    {
        $this->authorize('product.view');
    }

    public function updating($field): void
    {
        if (in_array($field, [
            'search',
            'status',
            'filter_is_packing',
            'filter_can_purchase',
            'filter_can_produce',
            'filter_can_consume',
            'filter_can_sell',
            'filter_can_stock',
        ])) {
            $this->resetPage();
        }
    }

    public function updatePerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->status !== '', fn ($q) => $q->where('is_active', (bool) ((int) $this->status)))
            ->when($this->filter_is_packing !== '', fn ($q) => $q->where('is_packing', (bool) ((int) $this->filter_is_packing)))
            ->when($this->filter_can_purchase !== '', fn ($q) => $q->where('can_purchase', (bool) ((int) $this->filter_can_purchase)))
            ->when($this->filter_can_produce !== '', fn ($q) => $q->where('can_produce', (bool) ((int) $this->filter_can_produce)))
            ->when($this->filter_can_consume !== '', fn ($q) => $q->where('can_consume', (bool) ((int) $this->filter_can_consume)))
            ->when($this->filter_can_sell !== '', fn ($q) => $q->where('can_sell', (bool) ((int) $this->filter_can_sell)))
            ->when($this->filter_can_stock !== '', fn ($q) => $q->where('can_stock', (bool) ((int) $this->filter_can_stock)))
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('livewire.product.view', compact('products'))
            ->with(['title_name' => $this->title ?? 'Products']);
    }
}
