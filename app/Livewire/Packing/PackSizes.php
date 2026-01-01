<?php

namespace App\Livewire\Packing;

use App\Models\PackSize;
use App\Models\PackSizeMaterial;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class PackSizes extends Component
{
    use AuthorizesRequests;

    public $title = 'Pack Sizes';
    public bool $canEditBom = false;
    public $products;
    public $packingMaterials;
    public $selectedProductId = '';
    public $packSizes;
    public $form = [
        'id' => null,
        'pack_qty' => '',
        'pack_uom' => '',
        'is_active' => true,
    ];
    public $bom = [];

    public function mount(): void
    {
        $this->authorize('packsize.view');
        $this->canEditBom = auth()->user()?->can('packsize.update_bom') ?? false;
        $this->products = Product::where('can_stock', true)
            ->where('is_packing', false)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $this->packingMaterials = Product::query()
            ->where('is_packing', true)
            ->where('can_purchase', true)
            ->where('can_consume', true)
            ->where('can_stock', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
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
        $this->bom = $this->canEditBom
            ? [
                ['material_product_id' => '', 'qty_per_pack' => null],
            ]
            : [];
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

        $materials = $record->packMaterials()->with('materialProduct')->orderBy('sort_order')->get();
        $this->bom = $materials->isNotEmpty()
            ? $materials->map(fn ($row) => [
                'material_product_id' => $row->material_product_id,
                'qty_per_pack' => $row->qty_per_pack,
                'uom' => $row->uom ?? optional($row->materialProduct)->uom,
            ])->toArray()
            : [['material_product_id' => '', 'qty_per_pack' => null]];
    }

    public function save(): void
    {
        $this->authorize($this->form['id'] ? 'packsize.update' : 'packsize.create');

        $rules = [
            'selectedProductId' => ['required', 'exists:products,id'],
            'form.pack_qty' => ['required', 'numeric', 'gt:0'],
            'form.pack_uom' => ['required', 'string', 'max:20'],
            'form.is_active' => ['boolean'],
        ];

        if ($this->canEditBom) {
            $rules['bom'] = ['array'];
            $rules['bom.*.material_product_id'] = ['nullable', 'exists:products,id'];
            $rules['bom.*.qty_per_pack'] = ['nullable', 'numeric', 'gt:0'];
        }

        $labels = [
            'selectedProductId' => 'product',
            'form.pack_qty' => 'pack quantity',
            'form.pack_uom' => 'pack unit',
        ];

        if ($this->canEditBom) {
            $labels['bom.*.material_product_id'] = 'packing material';
            $labels['bom.*.qty_per_pack'] = 'material quantity per pack';
        }

        $data = $this->validate($rules, [], $labels);

        $cleanBom = collect();
        $materialsLookup = collect();

        if ($this->canEditBom) {
            $cleanBom = collect($data['bom'])
                ->filter(fn ($row) => ! empty($row['material_product_id']) && isset($row['qty_per_pack']))
                ->values();

            $materialIds = $cleanBom->pluck('material_product_id')->unique()->filter();
            $materialsLookup = $materialIds->isNotEmpty()
                ? Product::whereIn('id', $materialIds)->get(['id', 'name', 'is_packing', 'can_purchase', 'can_consume', 'can_stock', 'uom'])->keyBy('id')
                : collect();

            foreach ($cleanBom as $index => $row) {
                $product = $materialsLookup->get((int) $row['material_product_id']);
                if (! $product || ! $product->is_packing || ! $product->can_purchase || ! $product->can_consume || ! $product->can_stock) {
                    $this->addError('bom.'.$index.'.material_product_id', 'Select a packing material that can be purchased, consumed, and stocked.');
                    return;
                }
            }

            if ($materialIds->count() !== $cleanBom->count()) {
                $this->addError('bom', 'Duplicate packing materials are not allowed.');
                return;
            }

            // Ensure options include existing (possibly inactive) materials to keep references available
            if (! $materialsLookup->isEmpty()) {
                $missing = $materialsLookup->keys()->diff($this->packingMaterials->pluck('id'));
                if ($missing->isNotEmpty()) {
                    $this->packingMaterials = $this->packingMaterials->concat(
                        Product::whereIn('id', $missing)->get()
                    );
                }
            }
        }

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

        if ($this->canEditBom) {
            $packSize->packMaterials()->delete();
            if ($cleanBom->isNotEmpty()) {
                $packSize->packMaterials()->createMany(
                    $cleanBom->values()->map(function ($row, $idx) use ($materialsLookup) {
                        $product = $materialsLookup->get((int) $row['material_product_id']);

                        return [
                            'material_product_id' => (int) $row['material_product_id'],
                            'qty_per_pack' => (float) $row['qty_per_pack'],
                            'uom' => $row['uom'] ?? ($product->uom ?? null),
                            'sort_order' => $idx,
                        ];
                    })->all()
                );
            }
        }

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

    public function addBomRow(): void
    {
        if (! $this->canEditBom) {
            return;
        }

        $this->bom[] = ['material_product_id' => '', 'qty_per_pack' => null];
    }

    public function removeBomRow(int $index): void
    {
        if (! $this->canEditBom) {
            return;
        }

        unset($this->bom[$index]);
        $this->bom = array_values($this->bom);
    }

    private function getProductUom(): ?string
    {
        $product = $this->products->firstWhere('id', (int) $this->selectedProductId);

        return $product->uom ?? null;
    }

    private function loadPackSizes(): void
    {
        $this->packSizes = $this->selectedProductId
            ? PackSize::with(['packMaterials.materialProduct'])->where('product_id', $this->selectedProductId)->orderBy('pack_qty')->get()
            : collect();
    }

    public function render()
    {
        return view('livewire.packing.pack-sizes', [
            'packSizesList' => $this->packSizes,
            'packingMaterialsList' => $this->packingMaterials,
            'canEditBom' => $this->canEditBom,
        ])->with(['title_name' => $this->title ?? 'Pack Sizes']);
    }
}
