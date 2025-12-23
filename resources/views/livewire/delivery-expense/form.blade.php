<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $expenseId ? 'Edit Delivery Expense' : 'Add Delivery Expense' }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('delivery-expenses.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    @if(session('error'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <form wire:submit.prevent="save" class="form-grid" style="margin-top: 1rem;">
        <div class="form-group span-2">
            <label for="dispatchSearch">Dispatch (search by number/date)</label>
            <input id="dispatchSearch" type="text" wire:model.live="dispatchSearch" class="input-field" placeholder="Search dispatch">
        </div>
        <div class="form-group span-2">
            <label for="dispatch_id">Dispatch <span style="color:red;">*</span></label>
            <select id="dispatch_id" wire:model.live="dispatch_id" class="input-field">
                <option value="">Select Dispatch</option>
                @foreach($dispatchOptions as $option)
                    <option value="{{ $option->id }}">
                        {{ $option->dispatch_no }} ({{ $option->dispatch_date?->toDateString() }})
                    </option>
                @endforeach
            </select>
            @error('dispatch_id') <span style="color:red;">{{ $message }}</span> @enderror
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
            <label for="expense_date">Expense Date</label>
            <input id="expense_date" type="date" wire:model.live="expense_date" class="input-field">
            @error('expense_date') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="expense_type">Type <span style="color:red;">*</span></label>
            <select id="expense_type" wire:model.live="expense_type" class="input-field">
                <option value="">Select Type</option>
                @foreach($expenseTypes as $type)
                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
            @error('expense_type') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group">
            <label for="amount">Amount <span style="color:red;">*</span></label>
            <input id="amount" type="number" step="0.01" min="0" wire:model.live="amount" class="input-field" placeholder="0.00">
            @error('amount') <span style="color:red;">{{ $message }}</span> @enderror
        </div>
        <div class="form-group span-2">
            <label for="remarks">Remarks</label>
            <textarea id="remarks" wire:model.live="remarks" class="input-field" placeholder="Notes"></textarea>
            @error('remarks') <span style="color:red;">{{ $message }}</span> @enderror
        </div>

        <div style="margin-top:1rem; grid-column: 1 / -1;">
            <button type="submit" class="btn-submit">{{ $expenseId ? 'Update' : 'Save' }} Expense</button>
        </div>
    </form>
</div>
