<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Suppliers</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('supplier.create')
                <a href="{{ route('suppliers.create') }}" class="btn-primary" wire:navigate>Add Supplier</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="searchName">Search Name</label>
            <input id="searchName" type="text" wire:model.live="searchName" class="input-field" placeholder="Supplier name">
        </div>
        <div class="form-group">
            <label for="searchMobile">Search Mobile</label>
            <input id="searchMobile" type="text" wire:model.live="searchMobile" class="input-field" placeholder="Mobile">
        </div>
        <div class="form-group">
            <label for="statusFilter">Status</label>
            <select id="statusFilter" wire:model.live="statusFilter" class="input-field">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_state_id">State</label>
            <select id="filter_state_id" wire:model.live="filter_state_id" class="input-field">
                <option value="">All</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter_district_id">District</label>
            <select id="filter_district_id" wire:model.live="filter_district_id" class="input-field">
                <option value="">All</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter_taluka_id">Taluka</label>
            <select id="filter_taluka_id" wire:model.live="filter_taluka_id" class="input-field">
                <option value="">All</option>
                @foreach($talukas as $taluka)
                    <option value="{{ $taluka->id }}">{{ $taluka->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="filter_village_id">Village</label>
            <select id="filter_village_id" wire:model.live="filter_village_id" class="input-field">
                <option value="">All</option>
                @foreach($villages as $village)
                    <option value="{{ $village->id }}">{{ $village->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mobile</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GSTIN</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Village / Taluka / District / State</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->supplier_code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->mobile }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->gstin }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($supplier->village)
                                {{ $supplier->village?->name }},
                                {{ $supplier->village?->taluka?->name }},
                                {{ $supplier->village?->taluka?->district?->name }},
                                {{ $supplier->village?->taluka?->district?->state?->name }}
                            @elseif($supplier->taluka)
                                -, {{ $supplier->taluka?->name }}, {{ $supplier->taluka?->district?->name }}, {{ $supplier->taluka?->district?->state?->name }}
                            @elseif($supplier->district)
                                -, -, {{ $supplier->district?->name }}, {{ $supplier->district?->state?->name }}
                            @elseif($supplier->state)
                                -, -, -, {{ $supplier->state?->name }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('suppliers.show', $supplier->id) }}" class="action-link" wire:navigate>View</a>
                                @can('supplier.update')
                                    <span aria-hidden="true">|</span>
                                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endcan
                                @can('supplier.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="deleteSupplier({{ $supplier->id }})"
                                        onclick="return confirm('Delete this supplier?')">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No suppliers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $suppliers->links() }}
    </div>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
