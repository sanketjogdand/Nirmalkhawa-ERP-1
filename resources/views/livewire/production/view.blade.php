<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Production Batches</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('production.create')
                <a href="{{ route('productions.create') }}" class="btn-primary" wire:navigate>New Batch</a>
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
            <label for="outputProduct">Output Product</label>
            <select id="outputProduct" wire:model.live="outputProductId" class="input-field">
                <option value="">All</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                @endforeach
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Output Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Output Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Recipe</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Yield</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Created By</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $batch->date ? $batch->date->toDateString() : '' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $batch->outputProduct->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $batch->actual_output_qty }} {{ $batch->output_uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $batch->recipe->name ?? 'Recipe' }} @if($batch->recipe) v{{ $batch->recipe->version }} @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($batch->yield_ratio)
                                {{ $batch->yield_ratio }} ({{ $batch->yield_pct }}%)
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $batch->is_locked ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $batch->createdByUser->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="{{ route('productions.show', $batch->id) }}" class="action-link" wire:navigate>View</a>
                            @can('production.update')
                                @if(! $batch->is_locked)
                                    <a href="{{ route('productions.edit', $batch->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endif
                            @endcan
                            @can('production.lock')
                                @if(! $batch->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmLock({{ $batch->id }})">Lock</button>
                                @endif
                            @endcan
                            @can('production.unlock')
                                @if($batch->is_locked)
                                    <button type="button" class="btn-primary" wire:click="confirmUnlock({{ $batch->id }})">Unlock</button>
                                @endif
                            @endcan
                            @can('production.delete')
                                @if(! $batch->is_locked)
                                    <button type="button" class="btn-danger" wire:click="confirmDelete({{ $batch->id }})">Delete</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No production found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $batches->links() }}
    </div>

    @if($showLockModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
            <div class="bg-white dark:bg-zinc-800 p-6 rounded shadow-lg max-w-md w-full">
                <h3 style="margin:0 0 10px 0;">Lock batch?</h3>
                <p style="margin:0 0 12px 0;">This cannot be undone unless an Admin unlocks it.</p>
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
                <h3 style="margin:0 0 10px 0;">Unlock batch?</h3>
                <p style="margin:0 0 12px 0;">Unlocked batches can be edited or deleted.</p>
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
                <h3 style="margin:0 0 10px 0;">Delete batch?</h3>
                <p style="margin:0 0 12px 0;">This will reverse stock postings. This action cannot be undone.</p>
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
