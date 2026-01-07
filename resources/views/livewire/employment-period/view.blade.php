<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h2 class="page-heading" style="margin-bottom:0;">Employment Periods</h2>
            @if($employee)
                <div style="color:gray; margin-top:4px;">{{ $employee->employee_code }} - {{ $employee->name }}</div>
            @endif
        </div>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employees.edit', $employeeId) }}" class="btn-secondary" wire:navigate>Back to Employee</a>
            <button type="button" class="btn-primary" wire:click="toggleForm">{{ $showForm ? 'Close Form' : 'Add Period' }}</button>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <div style="margin-top:0.75rem; display:flex; gap:12px; align-items:center;">
        @if($employee)
            <span style="color:gray;">First Joining: {{ $employee->joining_date?->format('d M Y') ?? '—' }}</span>
            <span style="color:gray;">Last Resignation: {{ $employee->resignation_date?->format('d M Y') ?? '—' }}</span>
        @endif
    </div>

    @if($showForm)
        <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
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
                <button type="submit" class="btn-submit">{{ $periodId ? 'Update' : 'Save' }} Period</button>
                <button type="button" class="btn-secondary" wire:click="resetForm">Reset</button>
            </div>
        </form>
    @endif

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Start Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">End Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    @php
                        $active = $period->start_date?->toDateString() <= $today
                            && ($period->end_date === null || $period->end_date?->toDateString() >= $today);
                    @endphp
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $period->start_date?->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $period->end_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $active ? 'ACTIVE' : 'ENDED' }}
                        </td>
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
                    <tr><td colspan="5" style="text-align: center;" class="px-4 py-2 border dark:border-zinc-700">No periods found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
