<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Customer Receipts</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('receipt.create')
                <a href="{{ route('customer-receipts.create') }}" class="btn-primary" wire:navigate>Add Receipt</a>
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
            <label for="customerId">Customer</label>
            <select id="customerId" wire:model.live="customerId" class="input-field">
                <option value="">All</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Customer</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Reference #</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($receipts as $receipt)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->receipt_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($receipt->customer)
                                <a href="{{ route('customers.show', $receipt->customer->id) }}" class="action-link" wire:navigate>
                                    {{ $receipt->customer->name }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($receipt->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->reference_no }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('customer-receipts.show', $receipt->id) }}" class="action-link" wire:navigate>View</a>
                                @can('receipt.update')
                                    <span aria-hidden="true">|</span>
                                    <a href="{{ route('customer-receipts.edit', $receipt->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endcan
                                @can('receipt.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="confirmDelete({{ $receipt->id }})">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No receipts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $receipts->links() }}
    </div>

    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="w-full max-w-md rounded-lg border border-zinc-200 bg-white text-zinc-900 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-50" style="padding:20px;">
                <h3 class="text-lg font-semibold mb-2">Confirm delete</h3>
                <p class="mb-4">Are you sure you want to delete this receipt?</p>
                <div class="flex justify-end gap-3">
                    <button type="button" class="btn-secondary" wire:click="$set('confirmingDeleteId', null)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="deleteReceipt">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
