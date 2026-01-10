<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Expense Categories</h2>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
    @endif

    <form wire:submit.prevent="save" style="margin-top:1rem;">
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Category Name <span style="color:red;">*</span></label>
                <input id="name" type="text" wire:model.live="name" class="input-field" placeholder="e.g. Office Supplies">
                @error('name') <span style="color:red;">{{ $message }}</span> @enderror
            </div>
        </div>

        <div style="margin-top:0.75rem; display:flex; gap:10px;">
            <button type="submit" class="btn-submit">{{ $categoryId ? 'Update Category' : 'Add Category' }}</button>
            @if($categoryId)
                <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('categoryId', null); $set('name',''); $set('default_gst_rate', null);">Cancel</button>
            @endif
        </div>
    </form>

    <div class="table-wrapper" style="margin-top:1.5rem;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $category->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px;">
                                @can('expense_category.update')
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="edit({{ $category->id }})">Edit</button>
                                @endcan
                                @can('expense_category.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;" wire:click="delete({{ $category->id }})" onclick="return confirm('Delete this category?')">Delete</button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No categories found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
