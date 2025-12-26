<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Pack Sizes</h2>
    </div>

    <div class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="selectedProductId">Product</label>
            <select id="selectedProductId" wire:model.live="selectedProductId" class="input-field">
                <option value="">Select product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
            @error('selectedProductId') <span style="color:red;">{{ $message }}</span> @enderror
            @if($selectedProductId)
                <div style="margin-top:6px; font-size:13px; color:gray;">UOM: {{ $products->firstWhere('id', (int) $selectedProductId)?->uom }}</div>
            @endif
        </div>
        @if($selectedProductId)
            <div class="form-group">
                <label for="pack_qty">Pack Quantity</label>
                <input id="pack_qty" type="number" step="0.001" wire:model.live="form.pack_qty" class="input-field" placeholder="e.g. 20">
                @error('form.pack_qty') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="pack_uom">Pack UOM</label>
                <input id="pack_uom" type="text" wire:model.live="form.pack_uom" class="input-field" placeholder="e.g. KG">
                @error('form.pack_uom') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Status</label>
                <div style="margin-top:6px;">
                    <label><input type="checkbox" wire:model.live="form.is_active"> Active</label>
                </div>
                @error('form.is_active') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group span-3">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                    <div>
                        <h3 style="margin:0; font-size:18px;">Packing Materials BOM</h3>
                        <small style="color:gray;">Materials consumed per pack (packing items only).</small>
                    </div>
                    <button type="button" class="btn-primary" wire:click="addBomRow">Add Material</button>
                </div>

                <div class="table-wrapper" style="margin-top:10px;">
                    <table class="product-table hover-highlight">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border dark:border-zinc-700">Material</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Qty / Pack</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bom as $index => $row)
                                @php $material = $packingMaterialsList->firstWhere('id', (int) ($row['material_product_id'] ?? 0)); @endphp
                                <tr>
                                    <td class="px-4 py-2 border dark:border-zinc-700">
                                        <select wire:model.live="bom.{{ $index }}.material_product_id" class="input-field">
                                            <option value="">Select packing material</option>
                                            @foreach($packingMaterialsList as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                                            @endforeach
                                        </select>
                                        @error('bom.'.$index.'.material_product_id') <span style="color:red;">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="px-4 py-2 border dark:border-zinc-700">
                                        <input type="number" step="0.001" wire:model.live="bom.{{ $index }}.qty_per_pack" class="input-field" placeholder="Qty">
                                        @error('bom.'.$index.'.qty_per_pack') <span style="color:red;">{{ $message }}</span> @enderror
                                    </td>
                                    <td class="px-4 py-2 border dark:border-zinc-700" style="min-width:80px;">
                                        {{ $row['uom'] ?? ($material->uom ?? '') }}
                                    </td>
                                    <td class="px-4 py-2 border dark:border-zinc-700">
                                        <button type="button" class="btn-danger" wire:click="removeBomRow({{ $index }})">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one material if required.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @error('bom') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: 1 / -1; margin-top: 8px;">
                <button type="button" class="btn-submit" wire:click="save">{{ $form['id'] ? 'Update Pack Size' : 'Add Pack Size' }}</button>
                @if($form['id'])
                    <button type="button" class="btn-secondary" style="margin-left:8px;" wire:click="resetForm">Cancel</button>
                @endif
            </div>
        @endif
    </div>

    @if($selectedProductId)
        <div class="table-wrapper" style="margin-top: 1.5rem;">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Pack Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">BOM</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packSizesList as $size)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($size->pack_qty, 3) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $size->pack_uom }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700" style="font-size:13px;">
                                @php $materials = $size->packMaterials->sortBy('sort_order'); @endphp
                                @if($materials->isEmpty())
                                    <span style="color:gray;">None</span>
                                @else
                                    <ul style="margin:0; padding-left:16px;">
                                        @foreach($materials as $mat)
                                            <li>{{ $mat->qty_per_pack }} {{ $mat->uom ?? optional($mat->materialProduct)->uom }} — {{ optional($mat->materialProduct)->name }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $size->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                @can('packsize.update')
                                    <button type="button" class="btn-secondary" wire:click="edit({{ $size->id }})">Edit</button>
                                @endcan
                                @can('packsize.delete')
                                    <button type="button" class="btn-danger" style="margin-left:6px;" wire:click="delete({{ $size->id }})" onclick="return confirm('Delete this pack size?')">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No pack sizes defined.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
