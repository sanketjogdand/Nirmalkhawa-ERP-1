<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $paymentId ? 'Edit Center Payment' : 'Record Center Payment' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('center-payments.view') }}" class="btn-primary" wire:navigate>Back to list</a>
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
            <label for="center_id">Center <span style="color:red;">*</span></label>
            <select id="center_id" wire:model.live="center_id" class="input-field" @disabled($isLocked)>
                <option value="">Select Center</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}">{{ $center->name }}</option>
                @endforeach
            </select>
            @error('center_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div class="form-group" style="margin-top:0;">
            <label for="balance_payable">Balance Payable</label>
            <input id="balancePayable" type="text" value="₹ {{ number_format($balancePayable, 2) }}" class="input-field" readonly>
            @if($balancePayable !== null)
                <span style="color:gray;">Excludes this payment{{ $paymentId ? ' (edit mode)' : '' }}</span>
            @else
                <span style="color:gray;">Select a center to calculate payable balance</span>
            @endif
        </div>

        <div class="form-group">
            <label for="payment_date">Payment Date <span style="color:red;">*</span></label>
            <input id="payment_date" type="date" wire:model.live="payment_date" class="input-field" @disabled($isLocked)>
            @error('payment_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount <span style="color:red;">*</span></label>
            <input id="amount" type="number" step="0.01" min="0" wire:model.live="amount" class="input-field" placeholder="0.00" @disabled($isLocked)>
            @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="payment_mode">Payment Mode <span style="color:red;">*</span></label>
            <select id="payment_mode" wire:model.live="payment_mode" class="input-field" @disabled($isLocked)>
                <option value="">Select Mode</option>
                @foreach($paymentModes as $mode)
                    <option value="{{ $mode }}">{{ $mode }}</option>
                @endforeach
            </select>
            @error('payment_mode') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="company_account">Company Account <span style="color:red;">*</span></label>
            <select id="company_account" wire:model.live="company_account" class="input-field" @disabled($isLocked)>
                <option value="">Select Account</option>
                @foreach($companyAccounts as $account)
                    <option value="{{ $account }}">{{ $account }}</option>
                @endforeach
            </select>
            @error('company_account') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="reference_no">Reference No</label>
            <input id="reference_no" type="text" wire:model.live="reference_no" class="input-field" placeholder="Cheque/UTR/Txn ID" @disabled($isLocked)>
            @error('reference_no') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes" @disabled($isLocked)></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1; display:flex; gap:12px; flex-wrap:wrap;">
            <button type="submit" class="btn-submit" @disabled($isLocked)>{{ $paymentId ? 'Update' : 'Save' }} Payment</button>
            <a href="{{ route('center-payments.view') }}" class="btn-secondary" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
