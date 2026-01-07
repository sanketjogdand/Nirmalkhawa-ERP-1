<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">
            {{ $packingId ? ($isReadOnly ? 'View Packing' : 'Edit Packing') : 'New Packing' }}
        </h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('packings.view') }}" class="btn-secondary" wire:navigate>Back to List</a>
        </div>
    </div>

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">This record is locked and cannot be edited.</div>
    @elseif($isReadOnly)
        <div class="toastr info" style="margin-top:0.5rem;">This record is read-only.</div>
    @endif

    @if($errors->has('form'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ $errors->first('form') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="date">Date</label>
            <input id="date" type="date" wire:model.live="date" class="input-field" @disabled($isLocked || $isReadOnly)>
            @error('date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="product_id">Product</label>
            <select id="product_id" wire:model.live="product_id" class="input-field" @disabled($isLocked || $isReadOnly)>
                <option value="">Select product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
            @error('product_id') <span style="color:red;">{{ $message }}</span> @enderror
            @if($product_id)
                <div style="margin-top:6px; font-size:13px; color:gray;">Available Bulk Stock: {{ number_format($availableBulk, 3) }} {{ $products->firstWhere('id', (int) $product_id)?->uom }}</div>
            @endif
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" placeholder="Optional remarks" @disabled($isLocked || $isReadOnly)></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
    </form>

    @if($product_id)
        @if(empty($packSizes))
            <div class="toastr danger" style="margin-top: 8px;">No pack sizes defined for this product. Please add pack sizes first.</div>
        @else
            <div style="margin: 1rem 0;">
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <div class="summary-card">
                        <div class="summary-title">Pack Total Quantity</div>
                        <div class="summary-value">{{ number_format($this->packTotalQuantity, 3) }} {{ $products->firstWhere('id', (int) $product_id)?->uom }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-title">Remaining Bulk</div>
                        <div class="summary-value" style="color: {{ $this->remainingBulk < 0 ? '#dc2626' : '#16a34a' }};">
                            {{ number_format($this->remainingBulk, 3) }} {{ $products->firstWhere('id', (int) $product_id)?->uom }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
                <table class="product-table hover-highlight">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border dark:border-zinc-700">Pack Size</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">No. of Packs</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Total Qty</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lines as $index => $line)
                            @php
                                $packSize = collect($packSizes)->firstWhere('id', (int) ($line['pack_size_id'] ?? 0));
                                $lineTotal = $packSize ? ((float) $packSize['pack_qty'] * (int) ($line['pack_count'] ?? 0)) : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <select wire:model.live="lines.{{ $index }}.pack_size_id" class="input-field" @disabled($isLocked || $isReadOnly)>
                                        <option value="">Select</option>
                                        @foreach($packSizes as $size)
                                            <option value="{{ $size['id'] }}">{{ number_format($size['pack_qty'], 3) }} {{ $size['pack_uom'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('lines.'.$index.'.pack_size_id') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    <input type="number" min="1" step="1" wire:model.live="lines.{{ $index }}.pack_count" class="input-field" placeholder="0" @disabled($isLocked || $isReadOnly)>
                                    @error('lines.'.$index.'.pack_count') <span style="color:red;">{{ $message }}</span> @enderror
                                </td>
                                <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($lineTotal, 3) }}</td>
                                <td class="px-4 py-2 border dark:border-zinc-700">
                                    @if(count($lines) > 1)
                                        <button type="button" class="btn-danger" wire:click="removeLine({{ $index }})" @disabled($isLocked || $isReadOnly)>Remove</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @error('lines') <div class="toastr danger" style="margin-top:8px;">{{ $message }}</div> @enderror

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn-secondary" wire:click="addLine" @disabled($isLocked || $isReadOnly)>Add Line</button>
                @if(! $isLocked && ! $isReadOnly)
                    <button type="button" class="btn-submit" wire:click="save">
                        {{ $packingId ? 'Save Changes' : 'Save Packing' }}
                    </button>
                @endif
            </div>
        @endif
    @endif

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
