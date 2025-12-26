<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $purchaseId ? 'Edit Purchase' : 'New Purchase' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <a href="{{ route('purchases.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if($isLocked)
                <span class="btn-danger" style="pointer-events:none; opacity:0.8;">Locked</span>
            @endif
        </div>
    </div>

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.75rem;">Purchase is locked.</div>
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
            <label for="purchase_date">Purchase Date <span style="color:red;">*</span></label>
            <input id="purchase_date" type="date" wire:model.live="purchase_date" class="input-field" @if($isLocked) disabled @endif>
            @error('purchase_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="supplier_bill_no">Supplier Bill No</label>
            <input id="supplier_bill_no" type="text" wire:model.live="supplier_bill_no" class="input-field" placeholder="Bill number" @if($isLocked) disabled @endif>
            @error('supplier_bill_no') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="supplier_bill_date">Supplier Bill Date</label>
            <input id="supplier_bill_date" type="date" wire:model.live="supplier_bill_date" class="input-field" @if($isLocked) disabled @endif>
            @error('supplier_bill_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes" rows="2" @if($isLocked) disabled @endif></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        </div>

    <div class="summary-container" style="margin-top:1rem;">
        <div class="summary-card">
            <div class="summary-heading">Totals</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td>{{ number_format($subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">GST Total</td>
                    <td>{{ number_format($total_gst, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Grand Total</td>
                    <td>{{ number_format($grand_total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin: 1rem 0; gap:12px; flex-wrap:wrap;">
        <h3 style="margin:0;">Line Items</h3>
        @if(! $isLocked)
            <button type="button" class="btn-primary" wire:click="addLine">Add Line</button>
        @endif
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Description</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Line Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $index => $line)
                    <tr>
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
                            <input type="text" wire:model.blur="lines.{{ $index }}.description" class="input-field" placeholder="Description" @if($isLocked) disabled @endif>
                            @error('lines.'.$index.'.description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="number" step="0.001" wire:model.blur="lines.{{ $index }}.qty" class="input-field" placeholder="Qty" @if($isLocked) disabled @endif>
                            @error('lines.'.$index.'.qty') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="text" wire:model.blur="lines.{{ $index }}.uom" class="input-field" placeholder="UOM" @if($isLocked) disabled @endif>
                            @error('lines.'.$index.'.uom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="number" step="0.001" wire:model.blur="lines.{{ $index }}.rate" class="input-field" placeholder="Rate" @if($isLocked) disabled @endif>
                            @error('lines.'.$index.'.rate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <select wire:model.blur="lines.{{ $index }}.gst_rate_percent" class="input-field" @if($isLocked) disabled @endif>
                                @foreach($gstRates as $rate)
                                    <option value="{{ $rate }}">{{ $rate }}%</option>
                                @endforeach
                            </select>
                            @error('lines.'.$index.'.gst_rate_percent') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['taxable_amount'] ?? 0), 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['gst_amount'] ?? 0), 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['line_total'] ?? 0), 2) }}</td>
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
                        <td colspan="10" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

        <div style="margin-top:1rem;">
        <button type="submit" class="btn-submit" @if($isLocked) disabled @endif>
            {{ $purchaseId ? 'Save Changes' : 'Save Purchase' }}
        </button>
    </div>
    </form>
</div>
