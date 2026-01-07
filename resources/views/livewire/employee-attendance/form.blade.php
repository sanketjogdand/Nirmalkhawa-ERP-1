<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $attendanceId ? 'Edit Attendance' : 'Add Attendance' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employee-attendance.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">This attendance is locked.</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="attendance_date">Attendance Date <span style="color:red;">*</span></label>
            <input id="attendance_date" type="date" wire:model.live="attendance_date" class="input-field" @disabled($isLocked || $attendanceId)>
            @error('attendance_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <input id="remarks" type="text" wire:model.live="remarks" class="input-field" placeholder="Remarks" @disabled($isLocked)>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <button type="button" class="btn-secondary" wire:click="markAllPresent" @disabled($isLocked)>Mark All Present</button>
            <button type="button" class="btn-secondary" wire:click="markAllAbsent" @disabled($isLocked)>Mark All Absent</button>
        </div>

        <div class="table-wrapper" style="grid-column: 1 / -1;">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Employee</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lines as $index => $line)
                        @php
                            $dayValue = $line['status'] === 'P' ? 1 : ($line['status'] === 'H' ? 0.5 : 0);
                        @endphp
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                {{ $line['employee_name'] ?? 'Employee #'.$line['employee_id'] }}
                                <input type="hidden" wire:model.live="lines.{{ $index }}.employee_id">
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <select wire:model.live="lines.{{ $index }}.status" class="input-field" @disabled($isLocked)>
                                    <option value="P">Present</option>
                                    <option value="A">Absent</option>
                                    <option value="H">Half Day</option>
                                </select>
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                <input type="text" wire:model.live="lines.{{ $index }}.remarks" class="input-field" placeholder="Remarks" @disabled($isLocked)>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No employees found for this date.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @error('lines') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit" @disabled($isLocked)>{{ $attendanceId ? 'Update' : 'Save' }} Attendance</button>
            <a href="{{ route('employee-attendance.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
