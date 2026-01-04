<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Rate Charts</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('ratechart.view')
                <a href="{{ route('rate-charts.calculator') }}" class="btn-primary" wire:navigate>Rate Calculator</a>
            @endcan
            @can('ratechart.create')
                <a href="{{ route('rate-charts.create') }}" class="btn-primary" wire:navigate>Create Rate Chart</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search Name/Code</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Name or code">
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
            <label for="effectiveOn">Effective On</label>
            <input id="effectiveOn" type="date" wire:model.live="effectiveOn" class="input-field">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Base (FAT/SNF)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Effective</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Assignments</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($charts as $chart)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600;">{{ $chart->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $chart->milk_type === 'CM' ? 'Cow Milk' : 'Buffalo Milk' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            ₹{{ number_format($chart->base_rate, 2) }}
                            <div style="font-size:12px; color:gray;">Base FAT {{ $chart->base_fat }} / SNF {{ $chart->base_snf }}</div>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($chart->effective_from)
                                {{ \Illuminate\Support\Carbon::parse($chart->effective_from)->format('d M Y') }}
                            @endif
                            -
                            @if($chart->effective_to)
                                {{ \Illuminate\Support\Carbon::parse($chart->effective_to)->format('d M Y') }}
                            @else
                                Ongoing
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $chart->assignments_count }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            @php $actions = []; @endphp
                            @php $actions[] = '<a href="'.route('rate-charts.show', $chart->id).'" class="action-link" wire:navigate>View / Manage</a>'; @endphp
                            @can('ratechart.update')
                                @php $actions[] = '<a href="'.route('rate-charts.edit', $chart->id).'" class="action-link" wire:navigate>Edit</a>'; @endphp
                            @endcan
                            @can('ratechart.delete')
                                @php $actions[] = '<button type="button" class="action-link" wire:click="deleteChart('.$chart->id.')" style="border:none; background:transparent; padding:0;">Delete</button>'; @endphp
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
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No rate charts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $charts->links() }}
    </div>
</div>
