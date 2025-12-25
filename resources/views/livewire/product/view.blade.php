<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Products</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('product.create')
                <a href="{{ route('products.create') }}" class="btn-primary" wire:navigate>Add Product</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search Name/Code</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Search...">
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
            <label for="filter_can_purchase">Can Purchase</label>
            <select id="filter_can_purchase" wire:model.live="filter_can_purchase" class="input-field">
                <option value="">All</option>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="form-group">
            <label for="filter_can_produce">Can Produce</label>
            <select id="filter_can_produce" wire:model.live="filter_can_produce" class="input-field">
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
        <div class="form-group">
            <label for="filter_can_sell">Can Sell</label>
            <select id="filter_can_sell" wire:model.live="filter_can_sell" class="input-field">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Category</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Packing Material</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Usage</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $product->name }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->category }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $product->is_packing ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <span style="display:inline-flex; flex-wrap:wrap; gap:6px;">
                                @if($product->is_packing)<span style="background:#dbeafe; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Packing</span>@endif
                                @if($product->can_purchase)<span style="background:#e5e7eb; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Purchase</span>@endif
                                @if($product->can_produce)<span style="background:#e5e7eb; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Produce</span>@endif
                                @if($product->can_consume)<span style="background:#e5e7eb; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Consume</span>@endif
                                @if($product->can_sell)<span style="background:#e5e7eb; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Sell</span>@endif
                                @if($product->can_stock)<span style="background:#e5e7eb; color:#111; padding:2px 6px; border-radius:4px; font-size:12px;">Stock</span>@endif
                            </span>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $product->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @can('product.update')
                                <a href="{{ route('products.edit', $product->id) }}" class="action-link" wire:navigate>Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $products->links() }}
    </div>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
