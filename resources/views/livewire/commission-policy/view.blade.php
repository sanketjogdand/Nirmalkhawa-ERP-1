<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Commission Policies</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('commissionpolicy.create')
                <a href="{{ route('commission-policies.create') }}" class="btn-primary" wire:navigate>Create Policy</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Name">
        </div>
        <div class="form-group">
            <label for="milkType">Milk Type</label>
            <select id="milkType" wire:model.live="milkType" class="input-field">
                <option value="">All</option>
                <option value="CM">Cow Milk</option>
                <option value="BM">Buffalo Milk</option>
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
                <option value="5">5</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Value (₹/L)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Active</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $policy)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $policy->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $policy->milk_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($policy->value, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $policy->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:10px; flex-wrap:wrap;">
                            @can('commissionpolicy.update')
                                <a href="{{ route('commission-policies.edit', $policy->id) }}" class="action-link" wire:navigate>Edit</a>
                                <button type="button" class="action-link" wire:click="toggleStatus({{ $policy->id }})" style="border:none; background:transparent; padding:0;">
                                    {{ $policy->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No policies found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $policies->links() }}
    </div>
</div>
