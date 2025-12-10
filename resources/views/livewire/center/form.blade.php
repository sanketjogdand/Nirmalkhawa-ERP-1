<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <h2 class="page-heading">{{ $centerId ? 'Edit' : 'Add' }} Center</h2>

    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Center Name<span style="color:red;">*</span></label>
                <input id="name" type="text" wire:model="name" class="input-field" required>
                @error('name')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="code">Code</label>
                <input id="code" type="text" wire:model="code" class="input-field">
                @error('code')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="contact_person">Contact Person</label>
                <input id="contact_person" type="text" wire:model="contact_person" class="input-field">
                @error('contact_person')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number<span style="color:red;">*</span></label>
                <input id="mobile" type="text" wire:model="mobile" class="input-field" placeholder="10 digits" required>
                @error('mobile')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group span-2">
                <label for="address">Address</label>
                <textarea id="address" wire:model="address" class="input-field" rows="2"></textarea>
                @error('address')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="state">State</label>
                <select id="state" wire:model="state_id" wire:change="updateStateId" class="input-field">
                    <option value="">Select State</option>
                    @foreach($states as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                @error('state_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="district">District</label>
                <select id="district" wire:model="district_id" wire:change="updateDistrictId" class="input-field">
                    <option value="">Select District</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
                @error('district_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="taluka">Taluka</label>
                <select id="taluka" wire:model="taluka_id" wire:change="updateTalukaId" class="input-field">
                    <option value="">Select Taluka</option>
                    @foreach($talukas as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('taluka_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="village">Village</label>
                <select id="village" wire:model="village_id" class="input-field">
                    <option value="">Select Village</option>
                    @foreach($villages as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
                @error('village_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="account_name">Account Name</label>
                <input id="account_name" type="text" wire:model="account_name" class="input-field">
                @error('account_name')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="account_number">Account Number</label>
                <input id="account_number" type="text" wire:model="account_number" class="input-field">
                @error('account_number')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="ifsc">IFSC</label>
                <input id="ifsc" type="text" wire:model="ifsc" class="input-field">
                @error('ifsc')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="branch">Branch</label>
                <input id="branch" type="text" wire:model="branch" class="input-field">
                @error('branch')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="status">Status<span style="color:red;">*</span></label>
                <select id="status" wire:model="status" class="input-field" required>
                    <option value="">Select</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                @error('status')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div style="margin-top: 1rem; text-align: center;">
            <button type="submit" class="btn-submit">{{ $centerId ? 'Update' : 'Add' }} Center</button>
        </div>
    </form>
</div>
