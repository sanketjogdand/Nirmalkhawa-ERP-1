<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $paymentId ? 'Edit General Expenses Payment' : 'New General Expenses Payment' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('general-expense-payments.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
    @endif

    <form wire:submit.prevent="save">
        <div class="form-grid" style="margin-top:1rem;">
            <div class="form-group">
                <label for="payment_date">Payment Date <span style="color:red;">*</span></label>
                <input id="payment_date" type="date" wire:model.live="payment_date" class="input-field">
                @error('payment_date') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="supplier_id">Supplier <span style="color:red;">*</span></label>
                <select id="supplier_id" wire:model.live="supplier_id" class="input-field">
                    <option value="">Select Supplier</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="paid_to">Paid To</label>
                <input id="paid_to" type="text" wire:model.live="paid_to" class="input-field" placeholder="Paid to">
                @error('paid_to') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="amount">Amount <span style="color:red;">*</span></label>
                <input id="amount" type="number" step="0.01" wire:model.live="amount" class="input-field" placeholder="Amount">
                @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="payment_mode">Payment Mode</label>
                <select id="payment_mode" wire:model.live="payment_mode" class="input-field">
                    <option value="">Select payment mode</option>
                    @foreach($paymentModes as $mode)
                        <option value="{{ $mode }}">{{ $mode }}</option>
                    @endforeach
                </select>
                @error('payment_mode') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label for="company_account">Company Account</label>
                <select id="company_account" wire:model.live="company_account" class="input-field">
                    <option value="">Select company account</option>
                    @foreach($companyAccounts as $account)
                        <option value="{{ $account }}">{{ $account }}</option>
                    @endforeach
                </select>
                @error('company_account') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
            <div class="form-group span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="2" placeholder="Remarks"></textarea>
                @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-top:1rem;">
            <button type="submit" class="btn-submit">
                {{ $paymentId ? 'Save Changes' : 'Save Payment' }}
            </button>
        </div>
    </form>
</div>
