<?php

namespace App\Livewire\Packing;

use App\Models\PackSize;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PackSizes extends Component
{
    use AuthorizesRequests;

    public $title = 'Pack Sizes';
    public $products;
    public $selectedProductId = '';
    public $packSizes;
    public $form = [
        'id' => null,
        'pack_qty' => '',
        'pack_uom' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorize('packsize.view');
        $this->products = Product::where('can_stock', true)->where('is_active', true)->orderBy('name')->get();
        $this->packSizes = collect();
    }

    public function updatedSelectedProductId(): void
    {
        $this->resetForm();
        $this->loadPackSizes();
    }

    public function resetForm(): void
    {
        $this->form = [
            'id' => null,
            'pack_qty' => '',
            'pack_uom' => $this->getProductUom(),
            'is_active' => true,
        ];
    }

    public function edit(int $packSizeId): void
    {
        $record = PackSize::where('product_id', $this->selectedProductId)->findOrFail($packSizeId);
        $this->authorize('packsize.update');

        $this->form = [
            'id' => $record->id,
            'pack_qty' => $record->pack_qty,
            'pack_uom' => $record->pack_uom,
            'is_active' => (bool) $record->is_active,
        ];
    }

    public function save(): void
    {
        $this->authorize($this->form['id'] ? 'packsize.update' : 'packsize.create');

        $data = $this->validate([
            'selectedProductId' => ['required', 'exists:products,id'],
            'form.pack_qty' => ['required', 'numeric', 'gt:0'],
            'form.pack_uom' => ['required', 'string', 'max:20'],
            'form.is_active' => ['boolean'],
        ], [], [
            'selectedProductId' => 'product',
            'form.pack_qty' => 'pack quantity',
            'form.pack_uom' => 'pack unit',
        ]);

        $packSize = $this->form['id']
            ? PackSize::where('product_id', $data['selectedProductId'])->findOrFail($this->form['id'])
            : new PackSize(['product_id' => (int) $data['selectedProductId']]);

        $packSize->fill([
            'pack_qty' => $data['form']['pack_qty'],
            'pack_uom' => $data['form']['pack_uom'] ?: $this->getProductUom(),
            'is_active' => (bool) $data['form']['is_active'],
        ]);

        $packSize->product_id = (int) $data['selectedProductId'];
        $packSize->save();

        $this->resetForm();
        $this->loadPackSizes();
        session()->flash('success', 'Pack size saved.');
    }

    public function delete(int $packSizeId): void
    {
        $this->authorize('packsize.delete');
        $record = PackSize::where('product_id', $this->selectedProductId)->findOrFail($packSizeId);

        if ($record->inventories()->exists() || $record->packingItems()->exists() || $record->unpackingItems()->exists()) {
            session()->flash('danger', 'Cannot delete pack size because it is already used in inventory or history.');
            return;
        }

        $record->delete();
        $this->loadPackSizes();
        session()->flash('success', 'Pack size deleted.');
    }

    private function getProductUom(): ?string
    {
        $product = $this->products->firstWhere('id', (int) $this->selectedProductId);

        return $product->uom ?? null;
    }

    private function loadPackSizes(): void
    {
        $this->packSizes = $this->selectedProductId
            ? PackSize::where('product_id', $this->selectedProductId)->orderBy('pack_qty')->get()
            : collect();
    }

    public function render()
    {
        return view('livewire.packing.pack-sizes', [
            'packSizesList' => $this->packSizes,
        ])->with(['title_name' => $this->title ?? 'Pack Sizes']);
    }
}
