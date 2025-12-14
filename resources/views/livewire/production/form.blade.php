<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $productionId ? 'Edit Production Batch' : 'New Production Batch' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('productions.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if ($isLocked)
        <div class="toastr warning" style="position:relative; top:auto; right:auto; margin:12px 0;">This batch is locked and cannot be edited.</div>
    @endif

    @if (session('danger'))
        <div class="toastr danger" style="position:relative; top:auto; right:auto; margin:12px 0;">{{ session('danger') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="date">Date</label>
            <input id="date" type="date" wire:model.live="date" class="input-field" {{ $isLocked ? 'disabled' : '' }}>
            @error('date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="output_product_id">Output Product</label>
            <select id="output_product_id" wire:model.live="output_product_id" class="input-field" {{ $isLocked ? 'disabled' : '' }}>
                <option value="">Select product</option>
                @foreach($outputProducts as $product)
                    @if($product->can_produce || $product->id == $output_product_id)
                        <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                    @endif
                @endforeach
            </select>
            @error('output_product_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="recipe_id">Recipe / Version</label>
            <select id="recipe_id" wire:model.live="recipe_id" class="input-field" {{ $isLocked ? 'disabled' : '' }}>
                <option value="">Select recipe</option>
                @foreach($recipes ?? [] as $recipeOption)
                    <option value="{{ $recipeOption->id }}">
                        {{ $recipeOption->name ?? 'Recipe' }} v{{ $recipeOption->version }} @if($recipeOption->is_active) (Active) @endif
                    </option>
                @endforeach
            </select>
            @error('recipe_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="actual_output_qty">Actual Output Qty</label>
            <input id="actual_output_qty" type="number" min="0" step="0.001" wire:model.live="actual_output_qty" class="input-field" {{ $isLocked ? 'disabled' : '' }}>
            @error('actual_output_qty') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="output_uom">Output UOM</label>
            <input id="output_uom" type="text" wire:model.live="output_uom" class="input-field" {{ $isLocked ? 'disabled' : '' }} readonly>
            @error('output_uom') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" {{ $isLocked ? 'disabled' : '' }}></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-3" style="margin-top:1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <h3 style="margin:0; font-size:18px;">Input Materials</h3>
                    <small style="color:gray;">Loaded from selected recipe. Enter actual quantities used.</small>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" class="btn-primary" wire:click="checkStocks" {{ $isLocked ? 'disabled' : '' }}>Check Stock</button>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top:10px;">
                <table class="product-table hover-highlight">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border dark:border-zinc-700" style="min-width:220px;">Material</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Qty per Output Unit</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Actual Qty Used</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Yield Base</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inputs as $index => $input)
                            <tr wire:key="input-{{ $index }}">
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <select wire:model.live="inputs.{{ $index }}.material_product_id" class="input-field" disabled>
                                        <option value="">Select material</option>
                                        @foreach($outputProducts as $product)
                                            @if($product->can_consume || $product->id == ($input['material_product_id'] ?? null))
                                                <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('inputs.'.$index.'.material_product_id') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <input type="number" min="0" step="0.001" wire:model.live="inputs.{{ $index }}.planned_qty" class="input-field" readonly>
                                    @error('inputs.'.$index.'.planned_qty') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <input type="number" min="0" step="0.001" wire:model.live="inputs.{{ $index }}.actual_qty_used" class="input-field" {{ $isLocked ? 'disabled' : '' }}>
                                    @error('inputs.'.$index.'.actual_qty_used') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <input type="text" wire:model.live="inputs.{{ $index }}.uom" class="input-field" readonly>
                                    @error('inputs.'.$index.'.uom') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">
                                    <input type="radio" name="yield_base" value="1" wire:model.live="inputs.{{ $index }}.is_yield_base" wire:click="setYieldBase({{ $index }})" {{ $isLocked ? 'disabled' : '' }}>
                                    @error('inputs.'.$index.'.is_yield_base') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Select a recipe to load inputs.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('inputs') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-3" style="display:flex; flex-direction:column; gap:6px;">
            <h4 style="margin:0;">Yield Preview</h4>
            @if($this->yieldPreview)
                <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center;">
                    <span>Ratio: <strong>{{ $this->yieldPreview['ratio'] }}</strong></span>
                    <span>Yield %: <strong>{{ $this->yieldPreview['pct'] }}%</strong></span>
                </div>
            @else
                <small style="color:gray;">Select a yield base line and enter quantities to preview yield.</small>
            @endif
        </div>

        @if(!empty($stockWarnings))
            <div class="form-group span-3" style="color:#b91c1c; background:#fef2f2; padding:10px; border-radius:6px;">
                <strong>Stock Warnings:</strong>
                <ul style="margin:6px 0 0 16px; padding:0; list-style:disc;">
                    @foreach($stockWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit" {{ $isLocked ? 'disabled' : '' }}>Save Production</button>
        </div>
    </form>
</div>
