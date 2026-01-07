<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $payrollId ? 'Edit Payroll' : 'Generate Payroll' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employee-payrolls.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">This payroll is locked.</div>
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
            <label for="payroll_month">Payroll Month <span style="color:red;">*</span></label>
            <input id="payroll_month" type="month" wire:model.live="payroll_month" class="input-field" @disabled($isLocked)>
            @error('payroll_month') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="total_days_in_month">Total Days</label>
            <input id="total_days_in_month" type="text" value="{{ $total_days_in_month }}" class="input-field" readonly>
        </div>
        <div class="form-group">
            <label for="present_days">Present Days</label>
            <input id="present_days" type="text" value="{{ number_format($present_days, 2) }}" class="input-field" readonly>
        </div>
        <div class="form-group">
            <label for="basic_pay">Basic Pay</label>
            <input id="basic_pay" type="text" value="₹ {{ number_format($basic_pay, 2) }}" class="input-field" readonly>
        </div>
        <div class="form-group">
            <label for="incentives_total">Incentives</label>
            <input id="incentives_total" type="text" value="₹ {{ number_format($incentives_total, 2) }}" class="input-field" readonly>
        </div>
        <div class="form-group">
            <label for="advance_outstanding">Advance Outstanding</label>
            <input id="advance_outstanding" type="text" value="₹ {{ number_format($advance_outstanding, 2) }}" class="input-field" readonly>
        </div>

        <div class="form-group">
            <label for="advance_deduction">Advance Deduction</label>
            <input id="advance_deduction" type="number" step="0.01" min="0" wire:model.live="advance_deduction" class="input-field" @disabled($isLocked)>
            @error('advance_deduction') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="other_additions">Other Additions</label>
            <input id="other_additions" type="number" step="0.01" min="0" wire:model.live="other_additions" class="input-field" @disabled($isLocked)>
            @error('other_additions') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="other_deductions">Other Deductions</label>
            <input id="other_deductions" type="number" step="0.01" min="0" wire:model.live="other_deductions" class="input-field" @disabled($isLocked)>
            @error('other_deductions') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="net_pay">Net Pay</label>
            <input id="net_pay" type="text" value="₹ {{ number_format($net_pay, 2) }}" class="input-field" readonly>
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Remarks" @disabled($isLocked)></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="table-wrapper" style="grid-column: 1 / -1;">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">From</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">To</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Present Days</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Segment Basic</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($segments as $segment)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $segment['from'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $segment['to'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $segment['salary_type'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹ {{ number_format($segment['rate_amount'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($segment['present_days'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹ {{ number_format($segment['segment_basic'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No salary segments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit" @disabled($isLocked)>{{ $payrollId ? 'Update' : 'Save' }} Payroll</button>
            <a href="{{ route('employee-payrolls.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
