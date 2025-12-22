<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $supplierId ? 'Edit Supplier' : 'Add Supplier' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('suppliers.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="name">Name <span style="color:red;">*</span></label>
            <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="Supplier name">
            @error('name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="supplier_code">Supplier Code</label>
            <input id="supplier_code" type="text" wire:model.live="supplier_code" class="input-field" placeholder="Unique short code">
            @error('supplier_code') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="contact_person">Contact Person</label>
            <input id="contact_person" type="text" wire:model.live="contact_person" class="input-field" placeholder="Contact person">
            @error('contact_person') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="mobile">Mobile</label>
            <input id="mobile" type="number" wire:model.live="mobile" min=8000000000 max=9999999999 class="input-field" placeholder="10 digit mobile">
            @error('mobile') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" wire:model.live="email" class="input-field" placeholder="Email">
            @error('email') <span style="color:red;">{{ $message }}</span> @enderror
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
            <input id="pincode" type="number" wire:model.live="pincode" min=100000 max=999999 class="input-field" placeholder="6 digit pincode">
            @error('pincode') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="grid-column: 1 / -1; font-weight:600; margin-top: 0.5rem;">Bank Details</div>
        <div class="form-group">
            <label for="account_name">Account Name</label>
            <input id="account_name" type="text" wire:model.live="account_name" class="input-field" placeholder="Account holder name">
            @error('account_name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="account_number">Account Number</label>
            <input id="account_number" type="text" wire:model.live="account_number" class="input-field" placeholder="Account number">
            @error('account_number') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="ifsc">IFSC</label>
            <input id="ifsc" type="text" wire:model.live="ifsc" class="input-field" placeholder="IFSC code">
            @error('ifsc') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="bank_name">Bank Name</label>
            <input id="bank_name" type="text" wire:model.live="bank_name" class="input-field" placeholder="Bank name">
            @error('bank_name') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="branch">Branch</label>
            <input id="branch" type="text" wire:model.live="branch" class="input-field" placeholder="Branch">
            @error('branch') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="upi_id">UPI ID</label>
            <input id="upi_id" type="text" wire:model.live="upi_id" class="input-field" placeholder="upi@bank">
            @error('upi_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" wire:model.live="notes" class="input-field" placeholder="Notes"></textarea>
            @error('notes') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="is_active">Status</label>
            <select id="is_active" wire:model.live="is_active" class="input-field">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            @error('is_active') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Supplier</button>
        </div>
    </form>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
