<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Pack Inventory</h2>
    </div>

    <div class="form-grid" style="margin-top: 1rem;">
        <div class="form-group">
            <label for="search">Search Product</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Name or code">
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

    @forelse($products as $product)
        @php
            $sizes = $packSizes->get($product->id) ?? collect();
            $totalPacked = 0;
        @endphp
        <div class="card" style="margin-bottom: 1rem; padding: 12px; border:1px solid #e5e7eb; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700;">{{ $product->name }}</div>
                    <div style="font-size:12px; color:gray;">{{ $product->code }} • {{ $product->uom }}</div>
                </div>
                <div class="summary-card" style="min-width:180px; margin:0;">
                    <div class="summary-title">Current Bulk Stock</div>
                    <div class="summary-value">{{ number_format($product->bulk_stock, 3) }} {{ $product->uom }}</div>
                </div>
            </div>

            <div class="table-wrapper" style="margin-top: 12px;">
                <table class="product-table hover-highlight">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border dark:border-zinc-700">Pack Size</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Pack Count</th>
                            <th class="px-4 py-2 border dark:border-zinc-700">Total Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sizes as $size)
                            @php
                                $inventoryRow = $packInventory->get($size->id);
                                $count = $inventoryRow ? (int) $inventoryRow->pack_count : 0;
                                $lineTotal = (float) $size->pack_qty * $count;
                                $totalPacked += $lineTotal;
                            @endphp
                            <tr>
                                <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($size->pack_qty, 3) }} {{ $size->pack_uom }}</td>
                                <td class="px-4 py-2 border dark:border-zinc-700">{{ $count }}</td>
                                <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($lineTotal, 3) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No pack sizes defined.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display:flex; gap:12px; margin-top:10px; flex-wrap:wrap;">
                <div class="summary-card" style="margin:0;">
                    <div class="summary-title">Total Packed Quantity</div>
                    <div class="summary-value">{{ number_format($totalPacked, 3) }} {{ $product->uom }}</div>
                </div>
                <div class="summary-card" style="margin:0;">
                    <div class="summary-title">Bulk + Packed Total</div>
                    <div class="summary-value">{{ number_format($product->bulk_stock + $totalPacked, 3) }} {{ $product->uom }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="toastr danger">No products with pack sizes found.</div>
    @endforelse

    <div class="pagination-wrapper">
        {{ $products->links() }}
    </div>
</div>
