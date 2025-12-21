<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Dispatch</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('dispatch.create')
                <a href="{{ route('dispatches.create') }}" class="btn-primary" wire:navigate>New Dispatch</a>
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
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="{{ \App\Models\Dispatch::STATUS_DRAFT }}">Draft</option>
                <option value="{{ \App\Models\Dispatch::STATUS_POSTED }}">Posted</option>
            </select>
        </div>
        <div class="form-group">
            <label for="deliveryMode">Delivery Mode</label>
            <select id="deliveryMode" wire:model.live="deliveryMode" class="input-field">
                <option value="">All</option>
                <option value="{{ \App\Models\Dispatch::DELIVERY_SELF }}">Self Pickup</option>
                <option value="{{ \App\Models\Dispatch::DELIVERY_COMPANY }}">Company Delivery</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Dispatch No</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Delivery Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Lines</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dispatches as $dispatch)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $dispatch->dispatch_no }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $dispatch->dispatch_date ? $dispatch->dispatch_date->toDateString() : '' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ str_replace('_', ' ', $dispatch->delivery_mode) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $dispatch->status }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $dispatch->is_locked ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $dispatch->lines_count }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="{{ route('dispatches.show', $dispatch->id) }}" class="action-link" wire:navigate>View</a>
                            @can('dispatch.update')
                                @if(! $dispatch->is_locked)
                                    <a href="{{ route('dispatches.edit', $dispatch->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endif
                            @endcan
                            @can('dispatch.post')
                                @if($dispatch->status === \App\Models\Dispatch::STATUS_DRAFT && ! $dispatch->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmPost({{ $dispatch->id }})">Post</button>
                                @endif
                            @endcan
                            @can('dispatch.lock')
                                @if(! $dispatch->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmLock({{ $dispatch->id }})">Lock</button>
                                @endif
                            @endcan
                            @can('dispatch.unlock')
                                @if($dispatch->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmUnlock({{ $dispatch->id }})">Unlock</button>
                                @endif
                            @endcan
                            @can('dispatch.delete')
                                @if(! $dispatch->is_locked)
                                    <button type="button" class="btn-danger" wire:click="confirmDelete({{ $dispatch->id }})">Delete</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No dispatches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $dispatches->links() }}
    </div>

    @if($showPostModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Post dispatch?</h3>
                <p style="margin:0 0 12px 0;">Stock will be reduced when posting.</p>
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
                <h3 style="margin:0 0 10px 0;">Lock dispatch?</h3>
                <p style="margin:0 0 12px 0;">Locked dispatches cannot be edited or deleted.</p>
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
                <h3 style="margin:0 0 10px 0;">Unlock dispatch?</h3>
                <p style="margin:0 0 12px 0;">Unlocked dispatches can be edited.</p>
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
                <h3 style="margin:0 0 10px 0;">Delete dispatch?</h3>
                <p style="margin:0 0 12px 0;">Stock effects will be reversed before deleting.</p>
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
