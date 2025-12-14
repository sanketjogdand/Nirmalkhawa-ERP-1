<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Recipe Details</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('recipes.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('recipe.update')
                <a href="{{ route('recipes.edit', $recipe->id) }}" class="btn-primary" wire:navigate>Edit</a>
            @endcan
        </div>
    </div>

    <div class="summary-container" style="margin-top:10px;">
        <div class="summary-card">
            <div class="summary-heading">Recipe Header</div>
            <table class="summary-table">
                <tr><td class="label">Output Product</td><td>{{ $recipe->outputProduct->name ?? 'N/A' }}</td></tr>
                <tr><td class="label">Recipe Name</td><td>{{ $recipe->name }}</td></tr>
                <tr><td class="label">Version</td><td>{{ $recipe->version }}</td></tr>
                <tr><td class="label">Batch Output</td><td>{{ $recipe->output_qty }} {{ $recipe->output_uom }}</td></tr>
                <tr><td class="label">Active</td><td>{{ $recipe->is_active ? 'Yes' : 'No' }}</td></tr>
                <tr><td class="label">Notes</td><td>{{ $recipe->notes ?? '-' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:10px;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Material</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty/Batch</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Yield Base</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recipe->items as $item)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $item->materialProduct->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $item->standard_qty }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $item->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $item->is_yield_base ? 'Yes' : 'No' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
