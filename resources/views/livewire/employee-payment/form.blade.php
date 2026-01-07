<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $paymentId ? 'Edit Employee Payment' : 'Record Employee Payment' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('employee-payments.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    @if($isLocked)
        <div class="toastr danger" style="margin-top:0.5rem;">This payment is locked.</div>
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

        <div class="form-group" style="margin-top:0;">
            <label for="advanceOutstanding">Advance Outstanding</label>
            <input id="advanceOutstanding" type="text" value="₹ {{ number_format($advanceOutstanding ?? 0, 2) }}" class="input-field" readonly>
            <span style="color:gray;">Includes payroll deductions</span>
        </div>
        <div class="form-group" style="margin-top:0;">
            <label for="balancePayable">Balance Payable</label>
            <input id="balancePayable" type="text" value="₹ {{ number_format($balancePayable ?? 0, 2) }}" class="input-field" readonly>
            <span style="color:gray;">Payroll net minus payments</span>
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date <span style="color:red;">*</span></label>
            <input id="payment_date" type="date" wire:model.live="payment_date" class="input-field" @disabled($isLocked)>
            @error('payment_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="payment_type">Payment Type <span style="color:red;">*</span></label>
            <select id="payment_type" wire:model.live="payment_type" class="input-field" @disabled($isLocked)>
                <option value="ADVANCE">ADVANCE</option>
                <option value="SALARY">SALARY</option>
                <option value="OTHER">OTHER</option>
            </select>
            @error('payment_type') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount <span style="color:red;">*</span></label>
            <input id="amount" type="number" step="0.01" min="0" wire:model.live="amount" class="input-field" placeholder="0.00" @disabled($isLocked)>
            @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="payment_mode">Payment Mode</label>
            <select id="payment_mode" wire:model.live="payment_mode" class="input-field" @disabled($isLocked)>
                <option value="">Select Mode</option>
                @foreach($paymentModes as $mode)
                    <option value="{{ $mode }}">{{ $mode }}</option>
                @endforeach
            </select>
            @error('payment_mode') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="company_account">Company Account</label>
            <select id="company_account" wire:model.live="company_account" class="input-field" @disabled($isLocked)>
                <option value="">Select Account</option>
                @foreach($companyAccounts as $account)
                    <option value="{{ $account }}">{{ $account }}</option>
                @endforeach
            </select>
            @error('company_account') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes" @disabled($isLocked)></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit" @disabled($isLocked)>{{ $paymentId ? 'Update' : 'Save' }} Payment</button>
            <a href="{{ route('employee-payments.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
