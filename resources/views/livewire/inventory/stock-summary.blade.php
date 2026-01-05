<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Stock Summary</h2>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search Product</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Name or code">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_is_packing">Packing Material</label>
            <select id="filter_is_packing" wire:model.live="filter_is_packing" class="input-field">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_can_stock">Can Stock</label>
            <select id="filter_can_stock" wire:model.live="filter_can_stock" class="input-field">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_can_sell">Can Sell</label>
            <select id="filter_can_sell" wire:model.live="filter_can_sell" class="input-field">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_can_consume">Can Consume</label>
            <select id="filter_can_consume" wire:model.live="filter_can_consume" class="input-field">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total Weight</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack Inventory</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Category</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php
                        $sizes = $packSizes->get($product->id) ?? collect();
                        $packRows = $packBalances->get($product->id) ?? collect();
                        $packedTotal = $sizes->sum(function ($size) use ($packRows) {
                            $invRow = $packRows->firstWhere('pack_size_id', $size->id);
                            $count = $invRow ? (int) $invRow->pack_balance : 0;

                            return (float) $size->pack_qty * $count;
                        });
                        $totalWeight = $product->stock_balance + $packedTotal;
                    @endphp
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $product->name }}
                            @if($product->is_packing)
                                <span style="background:#dbeafe; color:#111; padding:2px 6px; border-radius:4px; font-size:12px; margin-left:6px;">Packing</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600;">{{ number_format($totalWeight, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <div style="display:flex; flex-direction:column; gap:4px;">
                                <div style="font-size:13px; font-weight:700;">Bulk Stock: {{ number_format($product->stock_balance, 3) }} {{ $product->uom }}</div>
                                @if($sizes->isEmpty())
                                    <span style="color:gray;">No pack sizes</span>
                                @else
                                    @foreach($sizes as $size)
                                        @php
                                            $invRow = $packRows->firstWhere('pack_size_id', $size->id);
                                            $count = $invRow ? (int) $invRow->pack_balance : 0;
                                            $lineTotal = (float) $size->pack_qty * $count;
                                        @endphp
                                        <div style="font-size:13px;">
                                            {{ number_format($size->pack_qty, 3) }} {{ $size->pack_uom }}:
                                            <strong>{{ $count }}</strong> packs
                                            <span style="color:gray;">({{ number_format($lineTotal, 3) }})</span>
                                        </div>
                                    @endforeach
                                    <div style="font-size:13px; font-weight:700;">
                                        Total Packed: {{ number_format($packedTotal, 3) }} {{ $product->uom }}
                                        <span style="color:gray;">• Bulk+Pack: {{ number_format($product->stock_balance + $packedTotal, 3) }} {{ $product->uom }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->category }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
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
        {{ $products->links() }}
    </div>
</div>
