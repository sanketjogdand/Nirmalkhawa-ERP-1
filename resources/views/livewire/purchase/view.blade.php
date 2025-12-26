<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Purchases</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('purchase.create')
                <a href="{{ route('purchases.create') }}" class="btn-primary" wire:navigate>New Purchase</a>
            @endcan
        </div>
    </div>

    @if(session('danger'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
    @endif
    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <div class="form-grid">
        <div class="form-group">
            <label for="fromDate">Date From</label>
            <input id="fromDate" type="date" wire:model.live="fromDate" class="input-field">
        </div>
        <div class="form-group">
            <label for="toDate">Date To</label>
            <input id="toDate" type="date" wire:model.live="toDate" class="input-field">
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
        <div class="form-group">
            <label for="billNo">Bill No</label>
            <input id="billNo" type="text" wire:model.live="billNo" class="input-field" placeholder="Supplier bill no">
        </div>
        <div class="form-group">
            <label for="lockedFilter">Locked?</label>
            <select id="lockedFilter" wire:model.live="lockedFilter" class="input-field">
                <option value="">All</option>
                <option value="locked">Locked</option>
                <option value="unlocked">Unlocked</option>
            </select>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin: 1rem 0;">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            @can('purchase.lock')
                <button type="button" class="btn-primary" wire:click="confirmLock()" @disabled(count($selected) === 0)>Bulk Lock</button>
            @endcan
            @can('purchase.unlock')
                <button type="button" class="btn-primary" style="background:#059669;" wire:click="confirmUnlock(0)" @disabled(count($selected) === 0)>Bulk Unlock</button>
            @endcan
        </div>
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
                    <th class="px-4 py-2 border dark:border-zinc-700" style="width:36px;">
                        <input type="checkbox" wire:model.live="selectAll" onclick="event.stopPropagation();" aria-label="Select all on this page">
                    </th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Bill No</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Subtotal</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Grand Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="checkbox" value="{{ $purchase->id }}" wire:model.live="selected" @disabled($purchase->is_locked && !auth()->user()->can('purchase.unlock')) onclick="event.stopPropagation();">
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->purchase_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->supplier?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $purchase->supplier_bill_no ?? '—' }}
                            @if($purchase->supplier_bill_date)
                                <div style="font-size:12px; color:gray;">{{ $purchase->supplier_bill_date->format('Y-m-d') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->subtotal, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->total_gst, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->grand_total, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($purchase->is_locked)
                                <span title="By {{ $purchase->lockedBy?->name }} at {{ $purchase->locked_at?->format('d M Y H:i') }}">Locked</span>
                            @else
                                No
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('purchases.show', $purchase->id) }}" class="action-link" wire:navigate>View</a>
                                @if(! $purchase->is_locked)
                                    @can('purchase.update')
                                        <span aria-hidden="true">|</span>
                                        <a href="{{ route('purchases.edit', $purchase->id) }}" class="action-link" wire:navigate>Edit</a>
                                    @endcan
                                    @can('purchase.delete')
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="confirmDelete({{ $purchase->id }})">
                                            Delete
                                        </button>
                                    @endcan
                                    @can('purchase.lock')
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="confirmLock({{ $purchase->id }})">
                                            Lock
                                        </button>
                                    @endcan
                                @else
                                    @can('purchase.unlock')
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="confirmUnlock({{ $purchase->id }})">
                                            Unlock
                                        </button>
                                    @endcan
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No purchases found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $purchases->links() }}
    </div>

    @if($showLockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Lock purchases?</h3>
                <p style="margin:8px 0;">This cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showLockModal', false)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="lockConfirmed">Lock Now</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUnlockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Unlock purchase?</h3>
                <p style="margin:8px 0;">Unlocking allows edits.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showUnlockModal', false)">Cancel</button>
                    <button type="button" class="btn-primary" style="background:#059669;" wire:click="unlockConfirmed">Unlock Now</button>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Delete purchase?</h3>
                <p style="margin:8px 0;">This action cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showDeleteModal', false)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="deleteConfirmed">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
