<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Salary Rates - {{ $employee->name }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employees.edit', $employee->id) }}" class="btn-primary" wire:navigate>Back to Employee</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    @can('salary_rate.manage')
        <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="effective_from">Effective From <span style="color:red;">*</span></label>
                <input id="effective_from" type="date" wire:model.live="effective_from" class="input-field">
                @error('effective_from') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="salary_type">Salary Type <span style="color:red;">*</span></label>
                <select id="salary_type" wire:model.live="salary_type" class="input-field">
                    <option value="MONTHLY">MONTHLY</option>
                    <option value="DAILY">DAILY</option>
                </select>
                @error('salary_type') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="rate_amount">Rate Amount <span style="color:red;">*</span></label>
                <input id="rate_amount" type="number" step="0.01" wire:model.live="rate_amount" class="input-field" placeholder="0.00">
                @error('rate_amount') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <input id="remarks" type="text" wire:model.live="remarks" class="input-field" placeholder="Remarks">
                @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div style="grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
                <button type="submit" class="btn-submit">Add Rate</button>
            </div>
        </form>
    @endcan

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Effective From</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rates as $rate)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $rate->effective_from?->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $rate->salary_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹ {{ number_format($rate->rate_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $rate->remarks }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @can('salary_rate.manage')
                                <button type="button" class="action-link" wire:click="delete({{ $rate->id }})" style="border:none; background:transparent; padding:0;">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center;" class="px-4 py-2 border dark:border-zinc-700">No salary rates.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
