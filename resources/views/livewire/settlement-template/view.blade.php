<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Settlement Period Templates</h2>
        <a href="{{ route('center-settlements.view') }}" class="btn-primary" wire:navigate>Back to settlements</a>
    </div>

    <div style="margin-top:12px; padding:16px; border-radius:8px;" class="border dark:border-zinc-500">
        <h3 style="font-size:18px; margin-top:0;">{{ $form['id'] ? 'Edit Template' : 'New Template' }}</h3>
        <div class="form-grid">
            <div class="form-group">
                <label for="name">Name</label>
                <input id="name" type="text" wire:model.live="form.name" class="input-field" placeholder="e.g. 1-10">
                @error('form.name') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="start_day">Start Day</label>
                <input id="start_day" type="number" min="1" max="31" wire:model.live="form.start_day" class="input-field" placeholder="1">
                @error('form.start_day') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="end_day">End Day</label>
                <input id="end_day" type="number" min="1" max="31" wire:model.live="form.end_day" class="input-field" placeholder="10">
                @error('form.end_day') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" wire:model.live="form.end_of_month">
                    End of month?
                </label>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" wire:model.live="form.is_active" checked>
                    Active
                </label>
            </div>
        </div>
        <div style="margin-top:12px; display:flex; gap:12px;">
            <button type="button" class="btn-primary" wire:click="save">Save Template</button>
            <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="resetForm">Reset</button>
        </div>
    </div>

    <div style="margin-top:16px;" class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Name</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Start Day</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">End Day</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">End of Month</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Active</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $template->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $template->start_day }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $template->end_of_month ? '—' : $template->end_day }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $template->end_of_month ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $template->is_active ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <button type="button" class="action-link" wire:click="edit({{ $template->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No templates found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
