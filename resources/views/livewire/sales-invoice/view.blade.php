<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Sales Invoices</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('salesinvoice.create')
                <a href="{{ route('sales-invoices.create') }}" class="btn-primary" wire:navigate>New Invoice</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="fromDate">From Date</label>
            <input id="fromDate" type="date" wire:model.live="fromDate" class="input-field">
        </div>
        <div class="form-group">
            <label for="toDate">To Date</label>
            <input id="toDate" type="date" wire:model.live="toDate" class="input-field">
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
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="{{ \App\Models\SalesInvoice::STATUS_DRAFT }}">Draft</option>
                <option value="{{ \App\Models\SalesInvoice::STATUS_POSTED }}">Posted</option>
            </select>
        </div>
        <div class="form-group">
            <label for="locked">Locked</label>
            <select id="locked" wire:model.live="locked" class="input-field">
                <option value="">All</option>
                <option value="1">Locked</option>
                <option value="0">Unlocked</option>
            </select>
        </div>
    </div>

    <div class="per-page-select" style="margin: 1rem 0;">
        <label for="perPage">Records per page:</label>
        <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Invoice No</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Customer</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Subtotal</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Grand Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $invoice->invoice_no }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $invoice->invoice_date ? $invoice->invoice_date->toDateString() : '' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $invoice->customer->name ?? '' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $invoice->subtotal, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $invoice->total_gst, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $invoice->grand_total, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $invoice->status }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $invoice->is_locked ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="{{ route('sales-invoices.show', $invoice->id) }}" class="action-link" wire:navigate>View</a>
                            @can('salesinvoice.update')
                                @if(! $invoice->is_locked)
                                    <a href="{{ route('sales-invoices.edit', $invoice->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endif
                            @endcan
                            @can('salesinvoice.post')
                                @if($invoice->status === \App\Models\SalesInvoice::STATUS_DRAFT && ! $invoice->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmPost({{ $invoice->id }})">Post</button>
                                @endif
                            @endcan
                            @can('salesinvoice.lock')
                                @if(! $invoice->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmLock({{ $invoice->id }})">Lock</button>
                                @endif
                            @endcan
                            @can('salesinvoice.unlock')
                                @if($invoice->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmUnlock({{ $invoice->id }})">Unlock</button>
                                @endif
                            @endcan
                            @can('salesinvoice.delete')
                                @if(! $invoice->is_locked)
                                    <button type="button" class="btn-danger" wire:click="confirmDelete({{ $invoice->id }})">Delete</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $invoices->links() }}
    </div>

    @if($showPostModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Post invoice?</h3>
                <p style="margin:0 0 12px 0;">Posting freezes billing values (no stock movement).</p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn-danger" wire:click="$set('showPostModal', false)">Cancel</button>
                    <button class="btn-primary" wire:click="postConfirmed">Confirm Post</button>
                </div>
            </div>
        </div>
    @endif

    @if($showLockModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Lock invoice?</h3>
                <p style="margin:0 0 12px 0;">Locked invoices cannot be edited or deleted.</p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn-danger" wire:click="$set('showLockModal', false)">Cancel</button>
                    <button class="btn-primary" wire:click="lockConfirmed">Confirm Lock</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUnlockModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Unlock invoice?</h3>
                <p style="margin:0 0 12px 0;">Unlocked invoices can be edited by permitted users.</p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn-danger" wire:click="$set('showUnlockModal', false)">Cancel</button>
                    <button class="btn-primary" wire:click="unlockConfirmed">Confirm Unlock</button>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Delete invoice?</h3>
                <p style="margin:0 0 12px 0;">No inventory impact; billing record will be removed.</p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button class="btn-danger" wire:click="$set('showDeleteModal', false)">Cancel</button>
                    <button class="btn-primary" wire:click="deleteConfirmed">Confirm Delete</button>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
