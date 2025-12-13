<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $productId ? 'Edit Product' : 'Add Product' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('products.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="name">Name</label>
            <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="Product name">
            @error('name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="code">Code (optional)</label>
            <input id="code" type="text" wire:model.live="code" class="input-field" placeholder="Unique code">
            @error('code') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="uom">Unit of Measure</label>
            <select id="uom" wire:model.live="uom" class="input-field">
                <option value="">Select UOM</option>
                @foreach($uoms as $uomOption)
                    <option value="{{ $uomOption->name }}">{{ $uomOption->name }}</option>
                @endforeach
            </select>
            @error('uom') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="category">Category (optional)</label>
            <input id="category" type="text" wire:model.live="category" class="input-field" placeholder="Category for reporting">
            @error('category') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="hsn_code">HSN Code (optional)</label>
            <input id="hsn_code" type="text" wire:model.live="hsn_code" class="input-field" placeholder="HSN/SAC">
            @error('hsn_code') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="default_gst_rate">Default GST % (optional)</label>
            <select id="default_gst_rate" wire:model.live="default_gst_rate" class="input-field">
                <option value="">Select GST</option>
                <option value="0">0%</option>
                <option value="5">5%</option>
                <option value="18">18%</option>
            </select>
            @error('default_gst_rate') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group span-3" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
            <label style="font-weight:600; width:140px;">Usage Flags</label>
            <label><input type="checkbox" wire:model.live="can_purchase"> Can Purchase</label>
            <label><input type="checkbox" wire:model.live="can_produce"> Can Produce</label>
            <label><input type="checkbox" wire:model.live="can_consume"> Can Consume</label>
            <label><input type="checkbox" wire:model.live="can_sell"> Can Sell</label>
            <label><input type="checkbox" wire:model.live="can_stock"> Can Stock</label>
        </div>

        <div class="form-group">
            <label><input type="checkbox" wire:model.live="is_active"> Active</label>
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Product</button>
        </div>
    </form>
</div>
