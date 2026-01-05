<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Packing / Unpacking History</h2>
    </div>

    <div class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="productId">Product</label>
            <select id="productId" wire:model.live="productId" class="input-field">
                <option value="">All</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="operationType">Type</label>
            <select id="operationType" wire:model.live="operationType" class="input-field">
                <option value="">All</option>
                <option value="PACK">Pack</option>
                <option value="UNPACK">Unpack</option>
            </select>
        </div>
        <div class="form-group">
            <label for="fromDate">From</label>
            <input id="fromDate" type="date" wire:model.live="fromDate" class="input-field">
        </div>
        <div class="form-group">
            <label for="toDate">To</label>
            <input id="toDate" type="date" wire:model.live="toDate" class="input-field">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Size</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Count</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Bulk Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $row->product_name }}
                            <div style="font-size:12px; color:gray;">{{ $row->product_code }}</div>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->operation }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->pack_qty_snapshot, 3) }} {{ $row->pack_uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->pack_count }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="color: {{ $row->operation === 'PACK' ? '#dc2626' : '#16a34a' }}; font-weight:600;">
                            {{ $row->operation === 'PACK' ? '-' : '+' }}{{ number_format($row->bulk_qty, 3) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            Ref: {{ class_basename($row->reference_type) }} #{{ $row->reference_id }}
                            <div style="font-size:12px; color:gray;">Ledger: {{ $row->operation === 'PACK' ? 'PACK_BULK_OUT' : 'UNPACK_BULK_IN' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $records->links() }}
    </div>
</div>
