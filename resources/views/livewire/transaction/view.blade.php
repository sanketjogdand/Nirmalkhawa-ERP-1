<div class="product-container">
    <h2 class="page-heading">Transactions</h2>
    <a href="{{ route('transactions.create') }}" class="btn-primary">Add Transaction</a>
    <div class="per-page-select" style="margin-bottom: 1rem;">
        <label for="perPage">Records per page:</label>
        <select wire:model="perPage" wire:change="update_perPage" id="perPage">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Other Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">From</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">To</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Payment Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Company View</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Paid Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GSTIN</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Description</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tr)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ \Carbon\Carbon::parse($tr->transaction_date)->format('d-m-Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->other_type ?? '-' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->from_party }} ({{$tr->from_party_type}})</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->to_party }} ({{$tr->to_party_type}})</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->payment_mode }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->debit_credit ?? '-' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->total_amount ?? '-' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->paid_amount ?? '-' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->gstin ?? '-' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $tr->description }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('transactions.edit', $tr->id) }}" class="action-link">Edit</a>
                                @can('transaction.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="confirmDelete({{ $tr->id }})">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="px-4 py-2 border dark:border-zinc-700" style="text-align: center;">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $transactions->links() }}
    </div>

    @if($confirmingDeleteId)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Delete transaction?</h3>
                <p style="margin:8px 0;">This action cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('confirmingDeleteId', null)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="deleteTransaction">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
