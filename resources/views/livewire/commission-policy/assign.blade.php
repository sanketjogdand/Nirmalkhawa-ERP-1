<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Commission Assignments</h2>
    </div>

    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label for="policy">Commission Policy<span style="color:red;">*</span></label>
                <select id="policy" wire:model="commission_policy_id" class="input-field" required>
                    <option value="">Select Policy</option>
                    @foreach($policies as $policy)
                        <option value="{{ $policy->id }}">{{ $policy->code }} (रु. {{ $policy->value }})</option>
                    @endforeach
                </select>
                @error('commission_policy_id')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="centers">Centers<span style="color:red;">*</span></label>
                <select id="centers" wire:model="selectedCenters" class="input-field" multiple size="6">
                    @foreach($centers as $center)
                        <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                    @endforeach
                </select>
                @error('selectedCenters')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="effective_from">Effective From<span style="color:red;">*</span></label>
                <input id="effective_from" type="date" wire:model="effective_from" class="input-field" required>
                @error('effective_from')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="effective_to">Effective To</label>
                <input id="effective_to" type="date" wire:model="effective_to" class="input-field">
                @error('effective_to')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="is_active">Status</label>
                <select id="is_active" wire:model="is_active" class="input-field">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 1rem; text-align:right; display:flex; gap:10px; justify-content:flex-end;">
            <button type="button" class="btn-danger" wire:click="resetForm">Reset</button>
            <button type="submit" class="btn-primary">{{ $editId ? 'Update Assignment' : 'Save Assignment' }}</button>
        </div>
    </form>

    <div style="margin-top:1.5rem;" class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Center</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Policy</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Effective From</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Effective To</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->center?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->commissionPolicy?->code }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->commissionPolicy?->milk_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_from?->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_to?->format('d M Y') ?? 'Ongoing' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px;">
                            @can('commissionassignment.update')
                                <button class="action-link" type="button" wire:click="edit({{ $assignment->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No assignments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
