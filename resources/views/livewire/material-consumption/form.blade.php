<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <form wire:submit.prevent="save" class="product-container">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h2 class="page-heading" style="margin-bottom:0;">
                {{ $consumptionId ? 'Edit Material Consumption' : 'New Material Consumption' }}
            </h2>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="{{ route('material-consumptions.view') }}" class="btn-secondary" wire:navigate>Back to List</a>
            </div>
        </div>

        @if($isLocked)
            <div class="toastr danger" style="margin-top:0.5rem;">This record is locked and cannot be edited.</div>
        @endif

        @if($errors->has('lines'))
            <div class="toastr danger" style="margin-top:0.5rem;">{{ $errors->first('lines') }}</div>
        @endif
        @if(session('danger'))
            <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
        @endif

        <div class="form-grid">
            <div class="form-group">
                <label for="consumption_date">Date</label>
                <input id="consumption_date" type="date" wire:model.live="consumption_date" class="input-field" @disabled($isLocked)>
                @error('consumption_date') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="consumption_type">Type</label>
                <select id="consumption_type" wire:model.live="consumption_type" class="input-field" @disabled($isLocked)>
                    <option value="">Select type</option>
                    @foreach($consumptionTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('consumption_type') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" placeholder="Optional notes" @disabled($isLocked)></textarea>
                @error('remarks') <div class="error-text">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; margin: 1rem 0;">
            <h3 style="margin:0; font-size:18px;">Consumption Lines</h3>
            <button type="button" class="btn-secondary" wire:click="addLine" @disabled($isLocked)>Add Line</button>
        </div>

        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700" style="min-width:220px;">Product</th>
                        <th class="px-4 py-2 border dark:border-zinc-700" style="width:140px;">Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700" style="width:120px;">UOM</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                        <th class="px-4 py-2 border dark:border-zinc-700" style="width:60px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $index => $line)
                        @php $product = $products->firstWhere('id', (int) ($line['product_id'] ?? 0)); @endphp
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <select class="input-field" wire:model.live="lines.{{ $index }}.product_id" @disabled($isLocked)>
                                    <option value="">Select product</option>
                                    @foreach($products as $p)
                                        <option value="{{ $p->id }}">
                                            {{ $p->name }} {{ $p->is_packing ? '[Packing]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('lines.'.$index.'.product_id') <div class="error-text">{{ $message }}</div> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="number" step="0.001" class="input-field" wire:model.live="lines.{{ $index }}.qty" @disabled($isLocked)>
                                @error('lines.'.$index.'.qty') <div class="error-text">{{ $message }}</div> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="text" class="input-field" wire:model.live="lines.{{ $index }}.uom" @disabled($isLocked || ! $product) placeholder="{{ $product->uom ?? '' }}">
                                @error('lines.'.$index.'.uom') <div class="error-text">{{ $message }}</div> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="text" class="input-field" wire:model.live="lines.{{ $index }}.remarks" @disabled($isLocked)>
                                @error('lines.'.$index.'.remarks') <div class="error-text">{{ $message }}</div> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">
                                <button type="button" class="btn-danger" wire:click="removeLine({{ $index }})" @disabled($isLocked || count($lines) === 1)">×</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1.5rem; display:flex; justify-content:flex-end; gap:10px;">
            <a href="{{ route('material-consumptions.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
            <button type="submit" class="btn-primary" @disabled($isLocked)>Save</button>
        </div>
    </form>
</div>
