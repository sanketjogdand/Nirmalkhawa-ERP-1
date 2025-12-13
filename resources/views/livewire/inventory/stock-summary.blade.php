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
                    <th class="px-4 py-2 border dark:border-zinc-700">Current Stock</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Category</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600;">{{ number_format($product->stock_balance, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->category }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $products->links() }}
    </div>
</div>
