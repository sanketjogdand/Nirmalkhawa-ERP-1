<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $expenseId ? 'Edit General Expense' : 'New General Expense' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <a href="{{ route('general-expenses.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if($isLocked)
                <span class="btn-danger" style="pointer-events:none; opacity:0.8;">Locked</span>
            @endif
        </div>
    </div>

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.75rem;">Expense is locked.</div>
    @endif
    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="form-grid" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="expense_date">Expense Date <span style="color:red;">*</span></label>
                <input id="expense_date" type="date" wire:model.live="expense_date" class="input-field" @if($isLocked) disabled @endif>
                @error('expense_date') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" wire:model.live="supplier_id" class="input-field" @if($isLocked) disabled @endif>
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="invoice_no">Invoice No</label>
                <input id="invoice_no" type="text" wire:model.live="invoice_no" class="input-field" placeholder="Invoice no" @if($isLocked) disabled @endif>
                @error('invoice_no') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="attachment">Attachment</label>
                <input id="attachment" type="file" wire:model="attachment" class="input-field" @if($isLocked) disabled @endif>
                @if($attachment_path)
                    <div style="margin-top:6px; font-size:12px;">
                        <a href="{{ asset('storage/'.$attachment_path) }}" target="_blank" rel="noopener" class="action-link">View attachment</a>
                    </div>
                @endif
                @error('attachment') <span style="color:red;">{{ $message }}</span> @enderror
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
                        <td class="label">Taxable Total</td>
                        <td>{{ number_format($taxable_total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">GST Total</td>
                        <td>{{ number_format($gst_total, 2) }}</td>
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
                        <th class="px-4 py-2 border dark:border-zinc-700">Category</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Description</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Total</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $index => $line)
                        <tr wire:key="line-item-{{ $index }}">
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <select wire:model.blur="lines.{{ $index }}.category_id" class="input-field" @if($isLocked) disabled @endif>
                                    <option value="">Select category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('lines.'.$index.'.category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                                <input type="number" step="0.01" wire:model.blur="lines.{{ $index }}.rate" class="input-field" placeholder="Rate" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.rate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="number" step="0.01" wire:model.live="lines.{{ $index }}.taxable_amount" class="input-field" placeholder="Taxable" readonly @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.taxable_amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <select wire:model.blur="lines.{{ $index }}.gst_rate" class="input-field" @if($isLocked) disabled @endif>
                                    @foreach($gstRates as $rate)
                                        <option value="{{ $rate }}">{{ $rate }}%</option>
                                    @endforeach
                                </select>
                                @error('lines.'.$index.'.gst_rate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['gst_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['total_amount'] ?? 0), 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                @if(! $isLocked)
                                    <button type="button" class="btn-danger" wire:click="removeLine({{ $index }})">Remove</button>
                                @else
                                    <span style="color:gray;">Locked</span>
                                @endif
                            </td>
                        </tr>
                        <tr wire:key="line-item-vendor-{{ $index }}">
                            <td colspan="9" class="px-4 py-2 border dark:border-zinc-700">
                                <div style="font-weight:600; margin-bottom:6px;">Vendor Details</div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px;">
                                    <div class="form-group" style="margin:0;">
                                        <label style="font-size:12px;">Vendor</label>
                                        <select wire:model.blur="lines.{{ $index }}.vendor_id" class="input-field" @if($isLocked) disabled @endif>
                                            <option value="">Select vendor</option>
                                            @foreach($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('lines.'.$index.'.vendor_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-group" style="margin:0;">
                                        <label style="font-size:12px;">Vendor Name</label>
                                        <input type="text" wire:model.blur="lines.{{ $index }}.vendor_name" class="input-field" placeholder="Vendor name" @if($isLocked) disabled @endif>
                                        @error('lines.'.$index.'.vendor_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-group" style="margin:0;">
                                        <label style="font-size:12px;">Vendor Invoice No</label>
                                        <input type="text" wire:model.blur="lines.{{ $index }}.vendor_invoice_no" class="input-field" placeholder="Invoice no" @if($isLocked) disabled @endif>
                                        @error('lines.'.$index.'.vendor_invoice_no') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-group" style="margin:0;">
                                        <label style="font-size:12px;">Vendor Invoice Date</label>
                                        <input type="date" wire:model.blur="lines.{{ $index }}.vendor_invoice_date" class="input-field" @if($isLocked) disabled @endif>
                                        @error('lines.'.$index.'.vendor_invoice_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-group" style="margin:0;">
                                        <label style="font-size:12px;">Vendor GSTIN</label>
                                        <input type="text" wire:model.blur="lines.{{ $index }}.vendor_gstin" class="input-field" placeholder="GSTIN" @if($isLocked) disabled @endif>
                                        @error('lines.'.$index.'.vendor_gstin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-group" style="margin:0; display:flex; align-items:center; gap:8px; padding-top:18px;">
                                        <input id="rcm_{{ $index }}" type="checkbox" wire:model.blur="lines.{{ $index }}.is_rcm_applicable" @if($isLocked) disabled @endif>
                                        <label for="rcm_{{ $index }}" style="font-size:12px; margin:0;">RCM Applicable</label>
                                        @error('lines.'.$index.'.is_rcm_applicable') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem;">
            <button type="submit" class="btn-submit" @if($isLocked) disabled @endif>
                {{ $expenseId ? 'Save Changes' : 'Save Expense' }}
            </button>
        </div>
    </form>
</div>
