<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <h2 class="page-heading">{{ $rateChartId ? 'Edit' : 'Create' }} Rate Chart</h2>

    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label for="code">Code<span style="color:red;">*</span><small style="color:gray;"> (Auto-generated)</small></label>
                <input id="code" type="text" wire:model="code" class="input-field" placeholder="e.g. RC-CM-2024-001" required pattern="^RC-(CM|BM)-[0-9]{4}-[0-9]{3}$" title="Use format RC-CM-2024-001 or RC-BM-2024-001" readonly>
                @error('code')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="milk_type">Milk Type<span style="color:red;">*</span></label>
                <select id="milk_type" wire:model.live="milk_type" class="input-field" required>
                    <option value="CM">Cow Milk</option>
                    <option value="BM">Buffalo Milk</option>
                </select>
                @error('milk_type')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="base_rate">Base Rate (₹)<span style="color:red;">*</span></label>
                <input id="base_rate" type="number" step="0.01" wire:model.live="base_rate" class="input-field" required>
                @error('base_rate')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="base_fat">Base FAT<span style="color:red;">*</span></label>
                <input id="base_fat" type="number" step="0.01" wire:model.live="base_fat" class="input-field" required>
                @error('base_fat')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="base_snf">Base SNF<span style="color:red;">*</span></label>
                <input id="base_snf" type="number" step="0.01" wire:model.live="base_snf" class="input-field" required>
                @error('base_snf')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="effective_from">Effective From</label>
                <input id="effective_from" type="date" wire:model.live="effective_from" class="input-field">
                @error('effective_from')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="effective_to">Effective To</label>
                <input id="effective_to" type="date" wire:model.live="effective_to" class="input-field">
                @error('effective_to')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div style="margin-top: 1rem; text-align:center;">
            <button type="submit" class="btn-submit">{{ $rateChartId ? 'Update' : 'Create' }} Rate Chart</button>
        </div>
    </form>
</div>
