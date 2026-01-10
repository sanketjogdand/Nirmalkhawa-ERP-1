<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">General Expenses Payments</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('general_expense_payment.create')
                <a href="{{ route('general-expense-payments.create') }}" class="btn-primary" wire:navigate>New Payment</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
    @endif

    <div class="form-grid" style="margin-top:1rem;">
        <div class="form-group">
            <label for="dateFrom">From</label>
            <input id="dateFrom" type="date" wire:model.live="dateFrom" class="input-field">
        </div>
        <div class="form-group">
            <label for="dateTo">To</label>
            <input id="dateTo" type="date" wire:model.live="dateTo" class="input-field">
        </div>
        <div class="form-group">
            <label for="supplierId">Supplier</label>
            <select id="supplierId" wire:model.live="supplierId" class="input-field">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="perPage">Per Page</label>
            <select id="perPage" wire:model.live="perPage" class="input-field" wire:change="updatePerPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Paid To</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Account</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->supplier?->name ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->paid_to ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->payment_mode ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->company_account ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $payment->is_locked ? 'Yes' : 'No' }}
                            @if($payment->is_locked && $payment->locked_at)
                                <div style="font-size:12px; color:gray;">{{ $payment->lockedBy?->name }} | {{ $payment->locked_at->format('d M Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px;">
                                @can('general_expense_payment.view')
                                    <a href="{{ route('general-expense-payments.show', $payment->id) }}" class="action-link" wire:navigate>View</a>
                                @endcan
                                @can('general_expense_payment.update')
                                    @if(! $payment->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <a href="{{ route('general-expense-payments.edit', $payment->id) }}" class="action-link" wire:navigate>Edit</a>
                                    @endif
                                @endcan
                                @can('general_expense_payment.delete')
                                    @if(! $payment->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="deletePayment({{ $payment->id }})" onclick="return confirm('Delete this payment?')">Delete</button>
                                    @endif
                                @endcan
                                @can('general_expense_payment.lock')
                                    @if(! $payment->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="lockPayment({{ $payment->id }})">Lock</button>
                                    @endif
                                @endcan
                                @can('general_expense_payment.unlock')
                                    @if($payment->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="unlockPayment({{ $payment->id }})">Unlock</button>
                                    @endif
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        {{ $payments->links() }}
    </div>
</div>
