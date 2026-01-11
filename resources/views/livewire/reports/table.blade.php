<div class="product-container">
    @php View::share('title_name', $title_name ?? $title); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $title }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn-primary" wire:click="exportExcel">Export Excel</button>
            <button type="button" class="btn-primary" wire:click="exportPdf">Export PDF</button>
        </div>
    </div>

    <x-reports.filter-panel :filters="$filters" />

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
                    @foreach ($columns as $label)
                        <th class="px-4 py-2 border dark:border-zinc-700">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        @foreach ($columns as $key => $label)
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row[$key] ?? '' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $rows->links() }}
    </div>
</div>
