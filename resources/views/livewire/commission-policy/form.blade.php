<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <h2 class="page-heading">{{ $policyId ? 'Edit' : 'Create' }} Commission Policy</h2>

    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label for="code">Code<span style="color:red;">*</span></label>
                <input id="code" type="text" wire:model="code" class="input-field" placeholder="e.g. CP-CM-2024-001" required pattern="^CP-(CM|BM)-[0-9]{4}-[0-9]{3}$" title="Use format CP-CM-2024-001 or CP-BM-2024-001" readonly>
                @error('code')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="milk_type">Milk Type<span style="color:red;">*</span></label>
                <select id="milk_type" wire:model="milk_type" class="input-field" required>
                    <option value="CM">Cow Milk</option>
                    <option value="BM">Buffalo Milk</option>
                </select>
                @error('milk_type')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="value">Commission (₹/L)<span style="color:red;">*</span></label>
                <input id="value" type="number" step="0.01" wire:model="value" class="input-field" required>
                @error('value')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" wire:model="is_active" class="input-field">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                @error('is_active')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div style="margin-top: 1rem; text-align:center;">
            <button type="submit" class="btn-submit">{{ $policyId ? 'Update' : 'Create' }}</button>
        </div>
    </form>
</div>
