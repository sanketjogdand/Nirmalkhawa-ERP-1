<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Stock Ledger</h2>
    </div>

    <div class="form-grid">
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
            <label for="txnType">Transaction Type</label>
            <select id="txnType" wire:model.live="txnType" class="input-field">
                <option value="">All</option>
                <option value="GRN_IN">GRN In</option>
                <option value="PRODUCTION_OUT_IN">Production Output</option>
                <option value="UNPACK_BULK_IN">Unpack Bulk In</option>
                <option value="ADJUSTMENT_IN">Adjustment In</option>
                <option value="PRODUCTION_CONSUMPTION_OUT">Production Consumption Out</option>
                <option value="MATERIAL_CONSUMPTION_OUT">Material Consumption Out</option>
                <option value="PACK_BULK_OUT">Pack Bulk Out</option>
                <option value="PACK_MATERIAL_OUT">Pack Material Out</option>
                <option value="DISPATCH_OUT">Dispatch Out</option>
                <option value="ADJUSTMENT_OUT">Adjustment Out</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty In</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty Out</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Reference</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Created at</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ledgers as $entry)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($entry->txn_date)->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $entry->product_name ?? 'N/A' }}
                            @if($entry->product_code)
                                <div style="font-size:12px; color:gray;">{{ $entry->product_code }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $entry->txn_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600; color:#16a34a;">
                            {{ number_format($entry->qty_in, 3) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600; color:#dc2626;">
                            {{ number_format($entry->qty_out, 3) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $entry->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-size:12px; color:gray;">
                            @if($entry->ref_table && $entry->ref_id)
                                {{ $entry->ref_table }} #{{ $entry->ref_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $entry->remarks }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('d M Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No ledger entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px; display:flex; gap:16px; flex-wrap:wrap;">
        <div><strong>Total In:</strong> {{ number_format($totals['in'] ?? 0, 3) }}</div>
        <div><strong>Total Out:</strong> {{ number_format($totals['out'] ?? 0, 3) }}</div>
        <div><strong>Net:</strong> {{ number_format($totals['net'] ?? 0, 3) }}</div>
    </div>

    <div class="pagination-wrapper">
        {{ $ledgers->links() }}
    </div>
</div>
