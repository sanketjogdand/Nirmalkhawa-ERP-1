<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $recipeId ? 'Edit Recipe' : 'Create Recipe' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('recipes.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if (session('warning'))
        <div class="toastr warning" style="position:relative; top:auto; right:auto; margin-bottom:10px;">{{ session('warning') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group span-2">
            <label>Output Product</label>
            <input type="text" wire:model.live.debounce.300ms="outputProductSearch" class="input-field" placeholder="Search producible products">
            <select wire:model.live="output_product_id" class="input-field" size="4" style="margin-top:6px;">
                <option value="">Select product</option>
                @foreach($outputProducts as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name }} @if($product->code) ({{ $product->code }}) @endif
                        @if(! $product->can_produce) — override
                        @endif
                    </option>
                @endforeach
            </select>
            <small style="color:gray;">Defaults to products marked as producible; you can override if needed.</small>
            @error('output_product_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="name">Recipe Name</label>
            <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="Recipe name">
            @error('name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="version">Version</label>
            <input id="version" type="number" wire:model.live="version" class="input-field" min="1" step="1" readonly>
            @error('version') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="output_qty">Batch Output Qty (set 1 for per-unit)</label>
            <input id="output_qty" type="number" wire:model.live="output_qty" class="input-field" min="0" step="0.001">
            @error('output_qty') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="output_uom">Batch UOM (output unit)</label>
            <select id="output_uom" wire:model.live="output_uom" class="input-field">
                <option value="">Select UOM</option>
                @foreach($uoms as $uomOption)
                    <option value="{{ $uomOption->name }}">{{ $uomOption->name }}</option>
                @endforeach
            </select>
            @error('output_uom') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="notes">Notes/Remarks</label>
            <textarea id="notes" wire:model.live="notes" class="input-field" rows="2" placeholder="Optional notes about this recipe"></textarea>
            @error('notes') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label><input type="checkbox" wire:model.live="is_active"> Active recipe for this product</label>
        </div>

        <div class="form-group span-3" style="margin-top:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <h3 style="margin:0; font-size:18px;">Recipe Items</h3>
                    <small style="color:gray;">Type to search consumables; packing materials are listed separately.</small>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <input type="text" wire:model.live.debounce.300ms="materialSearch" class="input-field" placeholder="Search materials" style="width:220px;">
                    <button type="button" class="btn-primary" wire:click="addItem">Add Row</button>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top:10px;">
                <table class="product-table hover-highlight">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border dark:border-zinc-700" style="min-width:220px;">Material</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Qty/Batch</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Yield Base</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $item)
                            <tr>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <select wire:model.live="items.{{ $index }}.material_product_id" class="input-field">
                                        <option value="">Select material</option>
                                        @if(!empty($materialOptions['materials']) && $materialOptions['materials']->isNotEmpty())
                                            <optgroup label="Materials" class="bg-white dark:bg-zinc-800">
                                                @foreach($materialOptions['materials'] as $product)
                                                    <option value="{{ $product->id }}">
                                                        {{ $product->name }} @if($product->code) ({{ $product->code }}) @endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        @if(!empty($materialOptions['packingMaterials']) && $materialOptions['packingMaterials']->isNotEmpty())
                                            <optgroup label="Packing Materials" class="bg-white dark:bg-zinc-800">
                                                @foreach($materialOptions['packingMaterials'] as $product)
                                                    <option value="{{ $product->id }}">
                                                        {{ $product->name }} @if($product->code) ({{ $product->code }}) @endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </select>
                                    @error('items.'.$index.'.material_product_id') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <input type="number" min="0" step="0.001" wire:model.live="items.{{ $index }}.standard_qty" class="input-field" placeholder="Qty">
                                    @error('items.'.$index.'.standard_qty') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <select wire:model.live="items.{{ $index }}.uom" class="input-field">
                                        <option value="">Select UOM</option>
                                        @foreach($uoms as $uomOption)
                                            <option value="{{ $uomOption->name }}">{{ $uomOption->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('items.'.$index.'.uom') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">
                                    <input type="checkbox" wire:model.live="items.{{ $index }}.is_yield_base">
                                    @error('items.'.$index.'.is_yield_base') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">
                                    <button type="button" class="btn-danger" wire:click="removeItem({{ $index }})" onclick="return confirm('Remove this row?')">Remove</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one item.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('items') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Recipe</button>
        </div>
    </form>
</div>
