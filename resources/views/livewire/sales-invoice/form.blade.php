<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Sales Invoice</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            @if($invoice_no)
                <span style="font-weight:600;">#{{ $invoice_no }}</span>
            @elseif($invoiceNoPreview)
                <span style="font-size:13px; color:gray;">Next: {{ $invoiceNoPreview }}</span>
            @endif
            @if($status === \App\Models\SalesInvoice::STATUS_POSTED)
                <span class="btn-primary" style="pointer-events:none; opacity:0.7;">Posted</span>
            @endif
            @if($isLocked)
                <span class="btn-danger" style="pointer-events:none; opacity:0.7;">Locked</span>
            @endif
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="customer_id">Customer</label>
            <select id="customer_id" wire:model.live="customer_id" class="input-field" @if($isLocked) disabled @endif>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="invoice_date">Invoice Date</label>
            <input id="invoice_date" type="date" wire:model.live="invoice_date" class="input-field" @if($isLocked) disabled @endif>
            @error('invoice_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" rows="2" class="input-field" placeholder="Notes" @if($isLocked) disabled @endif></textarea>
            @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div class="summary-container" style="margin-top:1rem;">
        <div class="summary-card">
            <div class="summary-heading">Totals Preview</div>
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
        <div class="summary-card">
            <div class="summary-heading">Pull from Dispatch</div>
            <p style="margin:0 0 8px 0; font-size:13px;">Posted dispatches for the selected customer. Import lines and edit if needed (no stock effect).</p>
            <select wire:model="selectedDispatches" multiple class="input-field" size="4" @if($isLocked) disabled @endif>
                @foreach($dispatchOptions as $dispatch)
                    <option value="{{ $dispatch['id'] }}">{{ $dispatch['label'] }}</option>
                @endforeach
            </select>
            <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
                <button type="button" class="btn-primary" wire:click="importDispatches" @if($isLocked) disabled @endif>Import Lines</button>
                <span style="font-size:12px; color:gray;">Imports skip already-added dispatch lines.</span>
            </div>
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin: 1rem 0; gap:12px; flex-wrap:wrap;">
        <h3 style="margin:0;">Invoice Lines</h3>
        @if(! $isLocked)
            <button type="button" class="btn-primary" wire:click="addLine">Add Line</button>
        @endif
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Bulk Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Size</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Count</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate / Kg</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Line Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Dispatch Ref</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $index => $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <select wire:model.live="lines.{{ $index }}.product_id" class="input-field" @if($isLocked) disabled @endif>
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
                            <select wire:model.live="lines.{{ $index }}.sale_mode" class="input-field" @if($isLocked) disabled @endif>
                                <option value="{{ \App\Models\SalesInvoiceLine::MODE_BULK }}">Bulk</option>
                                <option value="{{ \App\Models\SalesInvoiceLine::MODE_PACK }}">Pack</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\SalesInvoiceLine::MODE_BULK)
                                <input type="number" step="0.001" wire:model.live="lines.{{ $index }}.qty_bulk" class="input-field" placeholder="Qty" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.qty_bulk') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\SalesInvoiceLine::MODE_PACK)
                                @php $productId = $line['product_id'] ?? null; $packs = $productId && isset($packSizesByProduct[$productId]) ? $packSizesByProduct[$productId] : []; @endphp
                                <select wire:model.live="lines.{{ $index }}.pack_size_id" class="input-field" @if($isLocked) disabled @endif>
                                    <option value="">Select pack</option>
                                    @foreach($packs as $pack)
                                        <option value="{{ $pack['id'] }}">{{ $pack['pack_qty'] }} {{ $pack['pack_uom'] }}</option>
                                    @endforeach
                                </select>
                                @error('lines.'.$index.'.pack_size_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\SalesInvoiceLine::MODE_PACK)
                                <input type="number" step="1" wire:model.live="lines.{{ $index }}.pack_count" class="input-field" placeholder="Count" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.pack_count') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ number_format((float) ($line['computed_total_qty'] ?? 0), 3) }} {{ $line['uom'] ?? '' }}
                            @if(!empty($line['source_dispatch_qty']) && (float) ($line['computed_total_qty'] ?? 0) !== (float) $line['source_dispatch_qty'])
                                <div style="font-size:12px; color:#c05621;">Diff from dispatch</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="number" step="0.001" wire:model.blur="lines.{{ $index }}.rate_per_kg" class="input-field" placeholder="Rate per kg" @if($isLocked) disabled @endif>
                            @error('lines.'.$index.'.rate_per_kg') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <select wire:model.live="lines.{{ $index }}.gst_rate_percent" class="input-field" @if($isLocked) disabled @endif>
                                @foreach($gstRates as $rate)
                                    <option value="{{ $rate }}">{{ $rate }}%</option>
                                @endforeach
                            </select>
                            @error('lines.'.$index.'.gst_rate_percent') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['taxable_amount'] ?? 0), 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['gst_amount'] ?? 0), 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) ($line['line_total'] ?? 0), 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-size:12px;">
                            @if(!empty($line['dispatch_line_id']))
                                <div>#{{ $line['dispatch_line_id'] }}</div>
                                @if(!empty($line['source_dispatch_qty']))
                                    <div style="color:gray;">Qty: {{ number_format((float) $line['source_dispatch_qty'], 3) }}</div>
                                @endif
                            @else
                                <span style="color:gray;">None</span>
                            @endif
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
                        <td colspan="13" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.5rem; display:flex; gap:12px; flex-wrap:wrap;">
        @if(! $isLocked)
            <button type="button" class="btn-primary" wire:click="saveAndPost">Save &amp; Post</button>
        @endif
        <a href="{{ route('sales-invoices.view') }}" class="btn-danger" wire:navigate>Cancel</a>
    </div>

    @error('form') <div class="toastr danger">{{ $message }}</div> @enderror
    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
