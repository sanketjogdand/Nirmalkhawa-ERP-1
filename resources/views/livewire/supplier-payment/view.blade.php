<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Supplier Payments</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('supplierpayment.create')
                <a href="{{ route('supplier-payments.create') }}" class="btn-primary" wire:navigate>Add Payment</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <div class="form-grid">
        <div class="form-group">
            <label for="dateFrom">Date From</label>
            <input id="dateFrom" type="date" wire:model.live="dateFrom" class="input-field">
        </div>
        <div class="form-group">
            <label for="dateTo">Date To</label>
            <input id="dateTo" type="date" wire:model.live="dateTo" class="input-field">
        </div>
        <div class="form-group">
            <label for="supplierId">Supplier</label>
            <select id="supplierId" wire:model.live="supplierId" class="input-field">
                <option value="">All</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin: 1rem 0;">
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Reference #</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($payment->supplier)
                                <a href="{{ route('suppliers.show', $payment->supplier->id) }}" class="action-link" wire:navigate>
                                    {{ $payment->supplier->name }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->reference_no }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->remarks }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('supplier-payments.show', $payment->id) }}" class="action-link" wire:navigate>View</a>
                                @can('supplierpayment.update')
                                    <span aria-hidden="true">|</span>
                                    <a href="{{ route('supplier-payments.edit', $payment->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endcan
                                @can('supplierpayment.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="deletePayment({{ $payment->id }})"
                                        onclick="return confirm('Delete this payment?')">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $payments->links() }}
    </div>
</div>
