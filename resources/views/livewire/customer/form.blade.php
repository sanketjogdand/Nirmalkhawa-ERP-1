<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $customerId ? 'Edit Customer' : 'Add Customer' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('customers.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="name">Name <span style="color:red;">*</span></label>
            <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="Customer name">
            @error('name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="customer_code">Customer Code</label>
            <input id="customer_code" type="text" wire:model.live="customer_code" class="input-field" placeholder="Unique short code">
            @error('customer_code') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="mobile">Mobile</label>
            <input id="mobile" type="text" wire:model.live="mobile" class="input-field" placeholder="10 digit mobile">
            @error('mobile') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="gstin">GSTIN</label>
            <input id="gstin" type="text" wire:model.live="gstin" class="input-field" placeholder="15 character GSTIN">
            @error('gstin') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="state_id">State</label>
            <select id="state_id" wire:model.live="state_id" class="input-field">
                <option value="">Select State</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
            @error('state_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="district_id">District</label>
            <select id="district_id" wire:model.live="district_id" class="input-field">
                <option value="">Select District</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
            </select>
            @error('district_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="taluka_id">Taluka</label>
            <select id="taluka_id" wire:model.live="taluka_id" class="input-field">
                <option value="">Select Taluka</option>
                @foreach($talukas as $taluka)
                    <option value="{{ $taluka->id }}">{{ $taluka->name }}</option>
                @endforeach
            </select>
            @error('taluka_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="village_id">Village</label>
            <select id="village_id" wire:model.live="village_id" class="input-field">
                <option value="">Select Village</option>
                @foreach($villages as $village)
                    <option value="{{ $village->id }}">{{ $village->name }}</option>
                @endforeach
            </select>
            @error('village_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="address_line">Address Line</label>
            <textarea name="address_line" id="address_line" wire:model.live="address_line" class="input-field" placeholder="Street / area"></textarea>
            @error('address_line') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="pincode">Pincode</label>
            <input id="pincode" type="text" wire:model.live="pincode" class="input-field" placeholder="6 digit pincode">
            @error('pincode') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label><input type="checkbox" wire:model.live="is_active"> Active</label>
            @error('is_active') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Customer</button>
        </div>
    </form>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
