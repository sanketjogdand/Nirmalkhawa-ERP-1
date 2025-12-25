<?php

namespace App\Livewire\Product;

use App\Models\Product;
use App\Models\Uom;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    use AuthorizesRequests;

    public $title = 'Products';

    public ?int $productId = null;
    public $name;
    public $code;
    public $uom = 'LTR';
    public $hsn_code;
    public $default_gst_rate;
    public $category;
    public $is_packing = false;
    public $can_purchase = true;
    public $can_produce = true;
    public $can_consume = true;
    public $can_sell = true;
    public $can_stock = true;
    public $is_active = true;
    public Collection $uoms;

    public function mount($product = null): void
    {
        $this->uoms = Uom::orderBy('name')->get();

        if ($product) {
            $record = Product::findOrFail($product);
            $this->authorize('product.update');
            $this->productId = $record->id;
            $this->fill($record->only([
                'name',
                'code',
                'uom',
                'hsn_code',
                'default_gst_rate',
                'category',
                'is_packing',
                'can_purchase',
                'can_produce',
                'can_consume',
                'can_sell',
                'can_stock',
                'is_active',
            ]));
        } else {
            $this->authorize('product.create');
            if ($this->uoms->isNotEmpty()) {
                $this->uom = $this->uom ?? $this->uoms->first()->name;
            }
        }
    }

    public function updatedIsPacking($value): void
    {
        if ((bool) $value) {
            $this->can_purchase = true;
            $this->can_stock = true;
            $this->can_consume = true;
            $this->can_sell = false;
            $this->can_produce = false;
        }
    }

    public function save()
    {
        $this->authorize($this->productId ? 'product.update' : 'product.create');

        if ($this->default_gst_rate === '') {
            $this->default_gst_rate = null;
        }

        $data = $this->validate($this->rules());

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($data);
            session()->flash('success', 'Product updated.');
        } else {
            Product::create($data);
            session()->flash('success', 'Product created.');
        }

        return redirect()->route('products.view');
    }

    public function render()
    {
        return view('livewire.product.form')
            ->with(['title_name' => $this->title ?? 'Products']);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', Rule::unique('products', 'code')->ignore($this->productId)],
            'uom' => ['required', 'string', 'max:20', Rule::in($this->uoms->pluck('name')->all())],
            'hsn_code' => ['nullable', 'string', 'max:50'],
            'default_gst_rate' => ['nullable', Rule::in([0, 5, 18])],
            'category' => ['nullable', 'string', 'max:100'],
            'is_packing' => ['boolean'],
            'can_purchase' => ['boolean'],
            'can_produce' => ['boolean'],
            'can_consume' => ['boolean'],
            'can_sell' => ['boolean'],
            'can_stock' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
