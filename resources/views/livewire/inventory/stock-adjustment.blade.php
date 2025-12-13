<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Stock Adjustments</h2>
    </div>

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="product_id">Product</label>
            <select id="product_id" wire:model.live="product_id" class="input-field">
                <option value="">Select product</option>
                @foreach($productsList as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
            @error('product_id') <span style="color:red;">{{ $message }}</span> @enderror
            @if($product_id)
                <div style="margin-top:6px; font-size:13px; color:gray;">Current Stock: {{ number_format($currentStock, 3) }} {{ $productsList->firstWhere('id', (int) $product_id)?->uom }}</div>
            @endif
        </div>

        <div class="form-group">
            <label for="txn_type">Entry Type</label>
            <select id="txn_type" wire:model.live="txn_type" class="input-field">
                <option value="{{ \App\Models\StockLedger::TYPE_OPENING }}">Opening</option>
                <option value="{{ \App\Models\StockLedger::TYPE_ADJ }}">Adjustment</option>
            </select>
            @error('txn_type') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Direction</label>
            <div style="display:flex; gap:12px; align-items:center; margin-top:6px;">
                <label><input type="radio" value="IN" wire:model.live="direction" @if($txn_type === \App\Models\StockLedger::TYPE_OPENING) disabled @endif> Increase</label>
                <label><input type="radio" value="OUT" wire:model.live="direction" @if($txn_type === \App\Models\StockLedger::TYPE_OPENING) disabled @endif> Decrease</label>
            </div>
            @error('direction') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="qty">Quantity</label>
            <input id="qty" type="number" step="0.001" wire:model.live="qty" class="input-field" placeholder="0.000">
            @error('qty') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="txn_datetime">Date & Time</label>
            <input id="txn_datetime" type="datetime-local" wire:model.live="txn_datetime" class="input-field">
            @error('txn_datetime') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" placeholder="Optional remarks"></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        @if($direction === 'OUT')
            <div class="form-group">
                <label><input type="checkbox" wire:model.live="allow_negative"> Allow negative balance (admin override)</label>
                @error('allow_negative') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
        @endif

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Entry</button>
        </div>
    </form>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
