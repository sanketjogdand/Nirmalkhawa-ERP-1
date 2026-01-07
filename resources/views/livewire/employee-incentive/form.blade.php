<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $incentiveId ? 'Edit Incentive' : 'Add Incentive' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employee-incentives.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">This incentive is locked.</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="employee_id">Employee <span style="color:red;">*</span></label>
            <select id="employee_id" wire:model.live="employee_id" class="input-field" @disabled($isLocked)>
                <option value="">Select Employee</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
            @error('employee_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="incentive_date">Date <span style="color:red;">*</span></label>
            <input id="incentive_date" type="date" wire:model.live="incentive_date" class="input-field" @disabled($isLocked)>
            @error('incentive_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="incentive_type">Type</label>
            <select id="incentive_type" wire:model.live="incentive_type" class="input-field" @disabled($isLocked)>
                <option value="">Select Type</option>
                <option value="PRODUCTION">PRODUCTION</option>
                <option value="SALES">SALES</option>
                <option value="FESTIVAL">FESTIVAL</option>
                <option value="OTHER">OTHER</option>
            </select>
            @error('incentive_type') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount <span style="color:red;">*</span></label>
            <input id="amount" type="number" step="0.01" min="0" wire:model.live="amount" class="input-field" placeholder="0.00" @disabled($isLocked)>
            @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Remarks" @disabled($isLocked)></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit" @disabled($isLocked)>{{ $incentiveId ? 'Update' : 'Save' }} Incentive</button>
            <a href="{{ route('employee-incentives.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
