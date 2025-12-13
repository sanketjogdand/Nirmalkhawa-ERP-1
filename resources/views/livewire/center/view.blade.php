<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Centers</h2>
        @can('center.create')
            <a href="{{ route('centers.create') }}" class="btn-primary" wire:navigate>
                Add Center
            </a>
        @endcan
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="searchName">Search Name</label>
            <input id="searchName" type="text" wire:model.live="searchName" class="input-field" placeholder="Center name">
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
            <label for="statusFilter">Status</label>
            <select id="statusFilter" wire:model.live="statusFilter" class="input-field">
                <option value="">All</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Center Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mobile</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Contact Person</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Address</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Village, Taluka, District, State</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Created At</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($centers as $row)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->mobile }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->contact_person }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->address }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($row->village?->name)
                                {{ $row->village?->name }},
                                {{ $row->village?->taluka?->name }},
                                {{ $row->village?->taluka?->district?->name }},
                                {{ $row->village?->taluka?->district?->state?->name }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->status }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->created_at?->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            @php $actions = []; @endphp
                            @php $actions[] = '<a href="'.route('centers.show', $row->id).'" class="action-link" wire:navigate>View</a>'; @endphp
                            @can('center.update')
                                @php $actions[] = '<a href="'.route('centers.edit', $row->id).'" class="action-link" wire:navigate>Edit</a>'; @endphp
                            @endcan
                            @can('center.delete')
                                @php $actions[] = '<button type="button" class="action-link" wire:click="toggleStatus('.$row->id.')" style="border:none; background:transparent; padding:0;">'.($row->status === 'Active' ? 'Deactivate' : 'Activate').'</button>'; @endphp
                            @endcan
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                @foreach($actions as $index => $action)
                                    {!! $action !!}
                                    @if($index < count($actions) - 1)
                                        <span aria-hidden="true">|</span>
                                    @endif
                                @endforeach
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center;" class="px-4 py-2 border dark:border-zinc-700">No Record Found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $centers->links() }}
    </div>
</div>
