<div class="table-wrapper" style="margin-top:0.5rem;">
    @if(session('success'))
        <div class="toastr success" style="margin:0.5rem 0;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid">
        <div class="form-group">
            <label for="start_date">Start Date <span style="color:red;">*</span></label>
            <input id="start_date" type="date" wire:model.live="start_date" class="input-field">
            @error('start_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="end_date">End Date</label>
            <input id="end_date" type="date" wire:model.live="end_date" class="input-field">
            @error('end_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <input id="remarks" type="text" wire:model.live="remarks" class="input-field" placeholder="Remarks">
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div style="grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit">{{ $periodId ? 'Update' : 'Add' }} Period</button>
            @if($periodId)
                <button type="button" class="btn-secondary" wire:click="resetForm">Cancel</button>
            @endif
        </div>
    </form>

    <table class="product-table hover-highlight" style="margin-top:1rem;">
        <thead>
            <tr>
                <th class="px-4 py-2 border dark:border-zinc-700">Start</th>
                <th class="px-4 py-2 border dark:border-zinc-700">End</th>
                <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periods as $period)
                <tr>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $period->start_date?->format('d M Y') }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $period->end_date?->format('d M Y') ?? '—' }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $period->remarks }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                        <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                            <button type="button" class="action-link" wire:click="edit({{ $period->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                            <span aria-hidden="true">|</span>
                            <button type="button" class="action-link" wire:click="delete({{ $period->id }})" style="border:none; background:transparent; padding:0;">Delete</button>
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align: center;" class="px-4 py-2 border dark:border-zinc-700">No periods.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
