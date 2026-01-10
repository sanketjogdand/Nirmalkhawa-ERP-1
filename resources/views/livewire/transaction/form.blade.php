<div class="product-container">
    <h2 class="page-heading">{{ $transactionId ? 'Edit' : 'Add' }} Transaction</h2>

    
    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label>Date</label>
                <input type="date" wire:model="transaction_date" class="input-field" />
            </div>
            <div class="form-group">
                <label>Type</label>
                <select wire:model="type" class="input-field">
                    <option value="">Select Type</option>
                    @foreach($transaction_types as $tt)
                        <option value="{{ $tt->name }}">{{ $tt->name }}</option>
                    @endforeach
                </select>
                @error('type') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>Other Type (if not in list)</label>
                <input type="text" wire:model="other_type" class="input-field" placeholder="Salary, Purchase, Sale, Expense..." />
            </div>
            <div class="form-group">
                <label>Reference</label>
                <input type="text" wire:model="reference" class="input-field" placeholder="Invoice, Salary ID, etc." />
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>From Party</label>
                <select wire:model="from_party_type" class="input-field" wire:change="get_previous_balance">
                    <option value="">Select Type</option>
                    <option value="Vendor">Vendor</option>
                    <option value="Customer">Customer</option>
                    <option value="Self">Self</option>
                    <option value="Employee">Employee</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <div x-data x-show="$wire.from_party_type == 'Vendor'" wire:change="get_previous_balance">
                    <label>Vendor</label>
                    <select wire:model="from_party" class="input-field">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->company_name ?? $vendor->name }}">{{ $vendor->company_name ?? $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.from_party_type == 'Customer'" wire:change="get_previous_balance">
                    <label>Customer</label>
                    <select wire:model="from_party" class="input-field">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->company_name ?? $customer->name }}">@if($customer->name) {{ $customer->name }} - @endif @if($customer->company_name) {{$customer->company_name}} - @endif {{$customer->contact}}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.from_party_type == 'Self'" wire:change="get_previous_balance">
                    <label>Self</label>
                    <select wire:model="from_party" class="input-field">
                        <option value="">Select Account</option>
                        @foreach($company_accounts as $ca)
                            <option value="{{ $ca->name }}">{{ $ca->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.from_party_type == 'Employee'" wire:change="get_previous_balance">
                    <label>Employee</label>
                    <select wire:model="from_party" class="input-field">
                        <option value="">Select Account</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->name }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.from_party_type == 'Other'" wire:change="get_previous_balance">
                    <label>Other</label>
                    <input type="text" wire:model="from_party" class="input-field" placeholder="Party Name" />
                </div>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>To Party</label>
                <select wire:model="to_party_type" class="input-field" wire:change="get_previous_balance">
                    <option value="">Select Type</option>
                    <option value="Vendor">Vendor</option>
                    <option value="Customer">Customer</option>
                    <option value="Self">Self</option>
                    <option value="Employee">Employee</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <div x-data x-show="$wire.to_party_type == 'Vendor'" wire:change="get_previous_balance">
                    <label>Vendor</label>
                    <select wire:model="to_party" class="input-field">
                        <option value="">Select Vendor</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->company_name ?? $vendor->name }}">{{ $vendor->company_name ?? $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.to_party_type == 'Customer'" wire:change="get_previous_balance">
                    <label>Customer</label>
                    <select wire:model="to_party" class="input-field">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->company_name ?? $customer->name }}">@if($customer->name) {{ $customer->name }} - @endif @if($customer->company_name) {{$customer->company_name}} - @endif {{$customer->contact}}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.to_party_type == 'Self'" wire:change="get_previous_balance">
                    <label>Self</label>
                    <select wire:model="to_party" class="input-field">
                        <option value="">Select Account</option>
                        @foreach($company_accounts as $ca)
                            <option value="{{ $ca->name }}">{{ $ca->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.to_party_type == 'Employee'" wire:change="get_previous_balance">
                    <label>Employee</label>
                    <select wire:model="to_party" class="input-field">
                        <option value="">Select Account</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->name }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data x-show="$wire.to_party_type == 'Other'" wire:change="get_previous_balance">
                    <label>Other</label>
                    <input type="text" wire:model="to_party" class="input-field" placeholder="Party Name" />
                </div>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Payment Mode</label>
                <select wire:model="payment_mode" class="input-field">
                <option value="">Select Mode</option>
                    @foreach($payment_modes as $mode)
                        <option value="{{ $mode->name }}">{{ $mode->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Inward / Outward</label>
                <select wire:model="debit_credit" class="input-field">
                    <option value="">Select</option>
                    <option value="Inward">Inward</option>
                    <option value="Outward">Outward</option>
                    <option value="Contra">Contra</option>
                </select>
                @error('debit_credit') <div class="text-red-600 text-sm">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label>GST Type</label>
                <select wire:model="gst_type" class="input-field">
                    <option value="">None</option>
                    <option value="input">Input GST</option>
                    <option value="output">Output GST</option>
                </select>
            </div>
            <div class="form-group">
                <label>Amount</label>
                <input type="number" wire:model="amount" wire:change="autoCalculateTotal()" min="0.01" step="0.01" class="input-field" />
            </div>
            <div class="form-group">
                <label>GST Percent</label>
                <select wire:change="autoCalculateTotal()" wire:model="gst_percent" class="input-field">
                    <option value="">Select GST Rate</option>
                    <option value="0">0 %</option>
                    <option value="5">5 %</option>
                    <option value="18">18 %</option>
                </select>
            </div>
            <div class="form-group">
                <label>GST Amount</label>
                <input type="number" wire:model="gst_amount" step="0.01" class="input-field" readonly/>
            </div>
            <div class="form-group">
                <label>Amount (incl. GST)</label>
                <input type="number" wire:model="total_amount" step="0.01" class="input-field" readonly/>
            </div>
            <div class="form-group">
                <label>{{ $transactionId ? 'Balance as on today' : 'Previous Balance' }}</label>
                <input type="number" wire:model="previous_balance_amount" step="0.01" class="input-field" readonly/>
            </div>
            <div class="form-group">
                <label>Total Amount {{ $transactionId ? '(wrong if updating and Amount is entered)' : '' }}</label>
                <input type="number" wire:model="final_total_amount" step="0.01" class="input-field" readonly/>
            </div>
            <div class="form-group">
                <label>Paid Amount</label>
                <input type="number" wire:model="paid_amount" step="0.01" class="input-field" />
            </div>
            <div class="form-group">
                <label>GSTIN (Party)</label>
                <input type="text" wire:model="gstin" class="input-field" />
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea wire:model="description" class="input-field"></textarea>
            </div>
        </div>

        <div style="margin-top: 1rem; text-align: center;">
            <button type="submit" class="btn-submit">{{ $transactionId ? 'Update' : 'Add' }} Transaction</button>
        </div>
    </form>
</div>
