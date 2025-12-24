<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Milk Intake</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('milk-intakes.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="center_id">Center</label>
            <select id="center_id" wire:model.live="center_id" class="input-field">
                <option value="">Select center</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                @endforeach
            </select>
            @error('center_id') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="date">Date</label>
            <input id="date" type="date" wire:model.live="date" class="input-field" autofocus>
            @error('date') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="shift">Shift</label>
            <select id="shift" wire:model.live="shift" class="input-field">
                <option value="{{ \App\Models\MilkIntake::SHIFT_MORNING }}">Morning</option>
                <option value="{{ \App\Models\MilkIntake::SHIFT_EVENING }}">Evening</option>
            </select>
            @error('shift') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="milk_type">Milk Type</label>
            <select id="milk_type" wire:model.live="milk_type" class="input-field">
                <option value="CM">Cow Milk</option>
                <option value="BM">Buffalo Milk</option>
            </select>
            @error('milk_type') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="qty_ltr">Quantity (Liters)</label>
            <input id="qty_ltr" type="number" step="0.01" wire:model.live="qty_ltr" class="input-field" placeholder="0.00">
            @error('qty_ltr') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="fat_pct">FAT %</label>
            <input id="fat_pct" type="number" step="0.1" wire:model.live="fat_pct" class="input-field" placeholder="0.00">
            @error('fat_pct') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="snf_pct">SNF %</label>
            <input id="snf_pct" type="number" step="0.1" wire:model.live="snf_pct" class="input-field" placeholder="0.00">
            @error('snf_pct') <span class="text-red-500" style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">Save Intake</button>
        </div>
    </form>
</div>
