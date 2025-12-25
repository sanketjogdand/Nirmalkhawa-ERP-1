<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Dispatch</h2>
        @if($dispatch_no)
            <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                <span style="font-weight:600;">#{{ $dispatch_no }}</span>
            </div>
        @endif
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="dispatch_date">Dispatch Date</label>
            <input id="dispatch_date" type="date" wire:model.live="dispatch_date" class="input-field" @if($isLocked) disabled @endif>
            @error('dispatch_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="delivery_mode">Delivery Mode</label>
            <select id="delivery_mode" wire:model.live="delivery_mode" class="input-field" @if($isLocked) disabled @endif>
                <option value="{{ \App\Models\Dispatch::DELIVERY_SELF }}">Self Pickup</option>
                <option value="{{ \App\Models\Dispatch::DELIVERY_COMPANY }}">Company Delivery</option>
            </select>
            @error('delivery_mode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="vehicle_no">Vehicle No</label>
            <input id="vehicle_no" type="text" wire:model.live="vehicle_no" class="input-field" placeholder="Optional" @if($isLocked) disabled @endif>
            @error('vehicle_no') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="driver_name">Driver Name</label>
            <input id="driver_name" type="text" wire:model.live="driver_name" class="input-field" placeholder="Optional" @if($isLocked) disabled @endif>
            @error('driver_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" rows="2" class="input-field" placeholder="Notes" @if($isLocked) disabled @endif></textarea>
            @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin: 1rem 0; gap:12px; flex-wrap:wrap;">
        <h3 style="margin:0;">Dispatch Lines</h3>
        @if(! $isLocked)
            <button type="button" class="btn-primary" wire:click="addLine">Add Line</button>
        @endif
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Customer</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Invoice (optional)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Bulk Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Size</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Count</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Available</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lines as $index => $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <select wire:model.live="lines.{{ $index }}.customer_id" class="input-field" @if($isLocked) disabled @endif>
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            @error('lines.'.$index.'.customer_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(! empty($invoiceOptions[$index]))
                                <select wire:model.live="lines.{{ $index }}.invoice_id" class="input-field" @if($isLocked) disabled @endif>
                                    <option value="">No invoice</option>
                                    @foreach($invoiceOptions[$index] as $invoice)
                                        <option value="{{ $invoice['id'] }}">{{ $invoice['label'] }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="input-field" value="None" disabled>
                            @endif
                            @error('lines.'.$index.'.invoice_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </td>
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
                                <option value="{{ \App\Models\DispatchLine::MODE_BULK }}">Bulk</option>
                                <option value="{{ \App\Models\DispatchLine::MODE_PACK }}">Pack</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\DispatchLine::MODE_BULK)
                                <input type="number" step="0.001" wire:model.live="lines.{{ $index }}.qty_bulk" class="input-field" placeholder="Qty" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.qty_bulk') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\DispatchLine::MODE_PACK)
                                <select wire:model.live="lines.{{ $index }}.pack_size_id" class="input-field" @if($isLocked) disabled @endif>
                                    <option value="">Select pack</option>
                                    @php $productId = $line['product_id'] ?? null; $packs = $productId && isset($packSizesByProduct[$productId]) ? $packSizesByProduct[$productId] : []; @endphp
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
                            @if(($line['sale_mode'] ?? '') === \App\Models\DispatchLine::MODE_PACK)
                                <input type="number" step="1" wire:model.live="lines.{{ $index }}.pack_count" class="input-field" placeholder="Count" @if($isLocked) disabled @endif>
                                @error('lines.'.$index.'.pack_count') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ number_format((float) ($line['computed_total_qty'] ?? 0), 3) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if(($line['sale_mode'] ?? '') === \App\Models\DispatchLine::MODE_BULK)
                                @php $pid = $line['product_id'] ?? null; @endphp
                                <span style="font-size:12px;">Bulk: {{ $pid && isset($bulkAvailabilityMap[$pid]) ? $bulkAvailabilityMap[$pid] : 0 }}</span>
                            @else
                                @php
                                    $pid = $line['product_id'] ?? null;
                                    $psid = $line['pack_size_id'] ?? null;
                                    $key = $pid && $psid ? ($pid.'-'.$psid) : null;
                                @endphp
                                <span style="font-size:12px;">Packs: {{ $key && isset($packAvailabilityMap[$key]) ? $packAvailabilityMap[$key] : 0 }}</span>
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
                        <td colspan="10" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">Add at least one line.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1.5rem; display:flex; gap:12px; flex-wrap:wrap;">
        @if(! $isLocked)
            <button type="button" class="btn-primary" wire:click="saveAndPost">Save &amp; Post</button>
        @endif
        <a href="{{ route('dispatches.view') }}" class="btn-danger" wire:navigate>Cancel</a>
    </div>

    @error('form') <div class="toastr danger">{{ $message }}</div> @enderror
    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
