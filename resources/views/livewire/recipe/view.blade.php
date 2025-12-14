<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Recipes</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('recipe.create')
                <a href="{{ route('recipes.create') }}" class="btn-primary" wire:navigate>Create Recipe</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search Name/Product</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Recipe name or product">
        </div>
        <div class="form-group">
            <label for="outputProductId">Output Product</label>
            <select id="outputProductId" wire:model.live="outputProductId" class="input-field">
                <option value="">All</option>
                @foreach($outputProducts as $product)
                    <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </div>

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Output Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Recipe</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Version</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Batch Output</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipes as $recipe)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $recipe->outputProduct->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <div style="font-weight:600;">{{ $recipe->name }}</div>
                            @if($recipe->notes)
                                <div style="font-size:12px; color:gray;">{{ \Illuminate\Support\Str::limit($recipe->notes, 80) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $recipe->version }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $recipe->output_qty }} {{ $recipe->output_uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $recipe->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            @php $actions = []; @endphp
                            @can('recipe.view')
                                @php $actions[] = '<a href="'.route('recipes.show', $recipe->id).'" class="action-link" wire:navigate>View</a>'; @endphp
                            @endcan
                            @can('recipe.update')
                                @php $actions[] = '<a href="'.route('recipes.edit', $recipe->id).'" class="action-link" wire:navigate>Edit</a>'; @endphp
                            @endcan
                            @can('recipe.create')
                                @php $actions[] = '<button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="duplicate('.$recipe->id.')">Duplicate</button>'; @endphp
                            @endcan
                            @can('recipe.activate')
                                @php $actions[] = '<button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="toggleActive('.$recipe->id.')">'.($recipe->is_active ? 'Deactivate' : 'Activate').'</button>'; @endphp
                            @endcan
                            @can('recipe.delete')
                                @php
                                    $actions[] = '<button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="delete('.$recipe->id.')" onclick="return confirm(\'Delete this recipe?\')">Delete</button>';
                                @endphp
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
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No recipes found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $recipes->links() }}
    </div>

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
</div>
