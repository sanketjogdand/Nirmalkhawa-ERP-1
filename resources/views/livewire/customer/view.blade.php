<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Customers</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('customer.create')
                <a href="{{ route('customers.create') }}" class="btn-primary" wire:navigate>Add Customer</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="searchName">Search Name</label>
            <input id="searchName" type="text" wire:model.live="searchName" class="input-field" placeholder="Customer name">
        </div>
        <div class="form-group">
            <label for="searchCode">Search Code</label>
            <input id="searchCode" type="text" wire:model.live="searchCode" class="input-field" placeholder="Code">
        </div>
        <div class="form-group">
            <label for="searchMobile">Search Mobile</label>
            <input id="searchMobile" type="text" wire:model.live="searchMobile" class="input-field" placeholder="Mobile">
        </div>
        <div class="form-group">
            <label for="searchGstin">Search GSTIN</label>
            <input id="searchGstin" type="text" wire:model.live="searchGstin" class="input-field" placeholder="GSTIN">
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
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->customer_code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->mobile }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->gstin }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($customer->village)
                                {{ $customer->village?->name }},
                                {{ $customer->village?->taluka?->name }},
                                {{ $customer->village?->taluka?->district?->name }},
                                {{ $customer->village?->taluka?->district?->state?->name }}
                            @elseif($customer->taluka)
                                -, {{ $customer->taluka?->name }}, {{ $customer->taluka?->district?->name }}, {{ $customer->taluka?->district?->state?->name }}
                            @elseif($customer->district)
                                -, -, {{ $customer->district?->name }}, {{ $customer->district?->state?->name }}
                            @elseif($customer->state)
                                -, -, -, {{ $customer->state?->name }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $customer->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('customers.show', $customer->id) }}" class="action-link" wire:navigate>View</a>
                                @can('customer.update')
                                    <span aria-hidden="true">|</span>
                                    <a href="{{ route('customers.edit', $customer->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endcan
                                @can('customer.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="deleteCustomer({{ $customer->id }})"
                                        onclick="return confirm('Delete this customer?')">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $customers->links() }}
    </div>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
