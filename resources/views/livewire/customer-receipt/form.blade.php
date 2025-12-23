<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $receiptId ? 'Edit Customer Receipt' : 'Customer Receipt' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('customer-receipts.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="customer_id">Customer <span style="color:red;">*</span></label>
            <select id="customer_id" wire:model.live="customer_id" class="input-field">
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            @error('customer_id') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="receipt_date">Receipt Date <span style="color:red;">*</span></label>
            <input id="receipt_date" type="date" wire:model.live="receipt_date" class="input-field">
            @error('receipt_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount <span style="color:red;">*</span></label>
            <input id="amount" type="number" step="0.01" min="0" wire:model.live="amount" class="input-field" placeholder="0.00">
            @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="payment_mode">Payment Mode <span style="color:red;">*</span></label>
            <select id="payment_mode" wire:model.live="payment_mode" class="input-field">
                <option value="">Select Mode</option>
                @foreach($paymentModes as $mode)
                    <option value="{{ $mode }}">{{ $mode }}</option>
                @endforeach
            </select>
            @error('payment_mode') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="company_account">Company Account <span style="color:red;">*</span></label>
            <select id="company_account" wire:model.live="company_account" class="input-field">
                <option value="">Select Account</option>
                @foreach($companyAccounts as $account)
                    <option value="{{ $account }}">{{ $account }}</option>
                @endforeach
            </select>
            @error('company_account') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="reference_no">Reference No</label>
            <input id="reference_no" type="text" wire:model.live="reference_no" class="input-field" placeholder="UTR/Cheque/Txn ID">
            @error('reference_no') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes"></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">{{ $receiptId ? 'Update' : 'Save' }} Receipt</button>
        </div>
    </form>
</div>
