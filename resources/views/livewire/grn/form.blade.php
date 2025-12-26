<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $grnId ? 'Edit GRN' : 'New Material Received (GRN)' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <a href="{{ route('grns.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if($isLocked)
                <span class="btn-danger" style="pointer-events:none; opacity:0.85;">Locked</span>
            @endif
        </div>
    </div>

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">GRN is locked.</div>
    @endif
    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="form-grid" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="supplier_id">Supplier <span style="color:red;">*</span></label>
                <select id="supplier_id" wire:model.live="supplier_id" class="input-field" @if($isLocked) disabled @endif>
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="grn_date">GRN Date <span style="color:red;">*</span></label>
                <input id="grn_date" type="date" wire:model.live="grn_date" class="input-field" @if($isLocked) disabled @endif>
                @error('grn_date') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="purchase_id">Purchase (optional)</label>
                <select id="purchase_id" wire:model.live="purchase_id" class="input-field" @if($isLocked) disabled @endif>
                    <option value="">No link</option>
                    @foreach($purchaseOptions as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('purchase_id') <span style="color:red;">{{ $message }}</span> @enderror
                <div style="font-size:12px; color:gray; margin-top:4px;">Linking is reference-only and does not post stock from Purchase.</div>
            </div>
            <div class="form-group span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes" rows="2" @if($isLocked) disabled @endif></textarea>
                @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
        </div>

        @if(!empty($purchaseLines))
            <div class="summary-card" style="margin-top:1rem;">
                <div class="summary-heading" style="text-align:left;">Purchase lines (reference only)</div>
                <div class="table-wrapper">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Billed Qty</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseLines as $line)
                                <tr>
                                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $line['product_name'] ?? 'Product #'.$line['product_id'] }}</td>
                                    <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line['qty'], 3) }}</td>
                                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $line['uom'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if(!empty($qtyWarnings))
            <div class="toastr warning" style="position:static; margin-top:0.75rem;">Some received quantities exceed billed quantities. Review highlighted rows below.</div>
        @endif

        <div style="display:flex; justify-content:space-between; align-items:center; margin: 1rem 0; gap:12px; flex-wrap:wrap;">
            <h3 style="margin:0;">GRN Lines</h3>
            @if(! $isLocked)
                <button type="button" class="btn-primary" wire:click="addLine">Add Line</button>
            @endif
        </div>

        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Received Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $index => $line)
                        <tr @if(isset($qtyWarnings[$index])) style="background:rgba(234,179,8,0.12);" @endif>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <select wire:model.blur="lines.{{ $index }}.product_id" class="input-field" @if($isLocked) disabled @endif>
                                    <option value="">Select product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }} @if($product->code) ({{ $product->code }}) @endif
                                            @if($product->is_packing) [Packing] @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('lines.'.$index.'.product_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="number" step="0.001" wire:model.blur="lines.{{ $index }}.received_qty" class="input-field" placeholder="Qty" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.received_qty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                @if(isset($qtyWarnings[$index]))
                                    <div style="font-size:12px; color:#92400e;">{{ $qtyWarnings[$index] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="text" wire:model.blur="lines.{{ $index }}.uom" class="input-field" placeholder="UOM" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.uom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="text" wire:model.blur="lines.{{ $index }}.remarks" class="input-field" placeholder="Remarks" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                @if(! $isLocked)
                                    <button type="button" class="btn-danger" wire:click="removeLine({{ $index }})">Remove</button>
                                @else
                                    <span style="color:gray;">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            <button type="submit" class="btn-submit" @if($isLocked) disabled @endif>
                {{ $grnId ? 'Save Changes' : 'Save GRN' }}
            </button>
        </div>
    </form>
</div>
