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
                <option value="{{ \App\Models\StockLedger::TYPE_OPENING }}">Opening</option>
                <option value="{{ \App\Models\StockLedger::TYPE_IN }}">In</option>
                <option value="{{ \App\Models\StockLedger::TYPE_OUT }}">Out</option>
                <option value="{{ \App\Models\StockLedger::TYPE_ADJ }}">Adjustment</option>
                <option value="{{ \App\Models\StockLedger::TYPE_PRODUCTION_IN }}">Production In</option>
                <option value="{{ \App\Models\StockLedger::TYPE_PRODUCTION_OUT }}">Production Out</option>
                <option value="{{ \App\Models\StockLedger::TYPE_TRANSFER }}">Transfer</option>
                <option value="{{ \App\Models\StockLedger::TYPE_PACKING_OUT }}">Packing Out</option>
                <option value="{{ \App\Models\StockLedger::TYPE_UNPACKING_IN }}">Unpacking In</option>
                <option value="{{ \App\Models\StockLedger::TYPE_DISPATCH_BULK_OUT }}">Dispatch Bulk Out</option>
                <option value="{{ \App\Models\StockLedger::TYPE_DISPATCH_PACK_OUT }}">Dispatch Pack Out</option>
                <option value="{{ \App\Models\StockLedger::TYPE_DISPATCH_BULK_DELETED }}">Dispatch Bulk Deleted</option>
                <option value="{{ \App\Models\StockLedger::TYPE_DISPATCH_PACK_DELETED }}">Dispatch Pack Deleted</option>
                <option value="{{ \App\Models\StockLedger::TYPE_GRN_IN }}">GRN In</option>
                <option value="{{ \App\Models\StockLedger::TYPE_GRN_REVERSAL }}">GRN Reversal</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Date/Time</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks / Reference</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ledgers as $entry)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $entry->txn_datetime?->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $entry->product?->name }}
                            <div style="font-size:12px; color:gray;">{{ $entry->product?->code }}</div>
                        </td>
                        @php
                            $typeLabel = $entry->txn_type;
                            if ($entry->txn_type === 'DISPATCH_OUT') {
                                $typeLabel = 'DISPATCH_BULK_OUT';
                            } elseif ($entry->txn_type === 'DISPATCH_PACK') {
                                $typeLabel = 'DISPATCH_PACK_OUT';
                            }
                        @endphp
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $typeLabel }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600; color: {{ $entry->is_increase ? '#16a34a' : '#dc2626' }};">
                            {{ $entry->is_increase ? '+' : '-' }}{{ number_format($entry->qty, 3) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $entry->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $entry->remarks }}
                            @if($entry->reference_type && $entry->reference_id)
                                <div style="font-size:12px; color:gray;">Ref: {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No ledger entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $ledgers->links() }}
    </div>
</div>
