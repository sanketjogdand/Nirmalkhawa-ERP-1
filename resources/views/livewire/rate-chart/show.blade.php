<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Rate Chart Details</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('rate-charts.view') }}" class="btn-primary" wire:navigate>Back to List</a>
            @can('ratechart.update')
                <a href="{{ route('rate-charts.edit', $rateChart->id) }}" class="btn-primary" wire:navigate>Edit Chart</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Code</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Base Rate</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Base FAT / SNF</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Effective</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $rateChart->code }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $rateChart->milk_type === 'CM' ? 'Cow Milk' : 'Buffalo Milk' }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($rateChart->base_rate, 2) }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $rateChart->base_fat }} / {{ $rateChart->base_snf }}</td>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        {{ $rateChart->effective_from?->format('d M Y') ?? 'N/A' }} -
                        {{ $rateChart->effective_to?->format('d M Y') ?? 'Ongoing' }}
                    </td>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $rateChart->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top:2rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
        <div class="card" style="border:1px solid #b1b1b1ff; border-radius:8px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="font-size:18px; font-weight:600; margin:0;">Add / Edit Slab</h3>
                @if($slabId)
                    <button class="btn-primary" type="button" wire:click="resetSlabForm" style="padding:4px 10px;">Cancel Edit</button>
                @endif
            </div>
            <form wire:submit.prevent="saveSlab">
                <div class="table-wrapper">
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border dark:border-zinc-700">Parameter</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Start</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">End</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Step</th>
                                <th class="px-4 py-2 border dark:border-zinc-700">Rate / Step</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-2 py-2 border dark:border-zinc-700">
                                    <select wire:model="param_type" class="input-field">
                                        <option value="FAT">FAT</option>
                                        <option value="SNF">SNF</option>
                                    </select>
                                    @error('param_type')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                                </td>
                                <td class="px-2 py-2 border dark:border-zinc-700">
                                    <input type="number" step="0.01" wire:model="start_val" class="input-field">
                                    @error('start_val')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                                </td>
                                <td class="px-2 py-2 border dark:border-zinc-700">
                                    <input type="number" step="0.01" wire:model="end_val" class="input-field">
                                    @error('end_val')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                                </td>
                                <td class="px-2 py-2 border dark:border-zinc-700">
                                    <input type="number" step="0.01" wire:model="step" class="input-field">
                                    @error('step')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                                </td>
                                <td class="px-2 py-2 border dark:border-zinc-700">
                                    <input type="number" step="0.01" wire:model="rate_per_step" class="input-field">
                                    @error('rate_per_step')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 12px; text-align:right; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-danger" wire:click="resetSlabForm">Reset</button>
                    <button type="submit" class="btn-primary">{{ $slabId ? 'Update Slab' : 'Add Slab' }}</button>
                </div>
            </form>
        </div>

        <div class="card" style="border:1px solid #b1b1b1ff; border-radius:8px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 style="font-size:18px; font-weight:600; margin:0;">Assign to Centers</h3>
                @if($assignmentId)
                    <button class="btn-primary" type="button" wire:click="resetAssignmentForm" style="padding:4px 10px;">Cancel Edit</button>
                @endif
            </div>
            <form wire:submit.prevent="saveAssignment">
                <div class="form-group">
                    <label for="centers">Centers<span style="color:red;">*</span></label>
                    <select id="centers" wire:model="selectedCenters" class="input-field" multiple size="5">
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                        @endforeach
                    </select>
                    @error('selectedCenters')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                </div>
                <div class="form-grid" style="margin-top:10px;">
                    <div class="form-group">
                        <label for="assignment_effective_from">Effective From<span style="color:red;">*</span></label>
                        <input id="assignment_effective_from" type="date" wire:model="assignment_effective_from" class="input-field">
                        @error('assignment_effective_from')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="assignment_effective_to">Effective To</label>
                        <input id="assignment_effective_to" type="date" wire:model="assignment_effective_to" class="input-field">
                        @error('assignment_effective_to')<span style="color:red; font-size:12px;">{{ $message }}</span>@enderror
                    </div>
                    {{-- <div class="form-group">
                        <label for="assignment_is_active">Status</label>
                        <select id="assignment_is_active" wire:model="assignment_is_active" class="input-field">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>--}}
                </div>
                <div style="margin-top:12px; text-align:right; display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-danger" wire:click="resetAssignmentForm">Reset</button>
                    <button type="submit" class="btn-primary">{{ $assignmentId ? 'Update Assignment' : 'Save Assignment' }}</button>
                </div>
            </form>
        </div>
    </div>

    <div style="margin-top:2rem; display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:1.5rem;">
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">FAT Slabs</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Step</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate / Step</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rateChart->slabs->where('param_type', 'FAT') as $slab)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->start_val }} - {{ $slab->end_val }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->step }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->rate_per_step }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px;">
                            @can('ratechart.update')
                                <button class="action-link" type="button" wire:click="editSlab({{ $slab->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                            @endcan
                            @can('ratechart.update')
                                    <button class="action-link" type="button" wire:click="deleteSlab({{ $slab->id }})" style="border:none; background:transparent; padding:0;">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No FAT slabs defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">SNF Slabs</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Step</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate / Step</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rateChart->slabs->where('param_type', 'SNF') as $slab)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->start_val }} - {{ $slab->end_val }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->step }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $slab->rate_per_step }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px;">
                            @can('ratechart.update')
                                <button class="action-link" type="button" wire:click="editSlab({{ $slab->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                            @endcan
                                @can('ratechart.update')
                                    <button class="action-link" type="button" wire:click="deleteSlab({{ $slab->id }})" style="border:none; background:transparent; padding:0;">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No SNF slabs defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:2rem;">
        <h3 style="font-size:18px; font-weight:600; margin-bottom:10px;">Assignments</h3>
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Center</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Effective From</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Effective To</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rateChart->assignments->sortByDesc('effective_from') as $assignment)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->center?->name }} ({{ $assignment->center?->code }})</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_from?->format('d M Y') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->effective_to?->format('d M Y') ?? 'Ongoing' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700" style="display:flex; gap:8px;">
                                @can('ratechart.assign')
                                    <button class="action-link" type="button" wire:click="editAssignment({{ $assignment->id }})" style="border:none; background:transparent; padding:0;">Edit</button>
                                @endcan
                                {{-- @can('ratechart.assign')
                                    <button class="action-link" type="button" wire:click="toggleAssignment({{ $assignment->id }})" style="border:none; background:transparent; padding:0;">
                                        {{ $assignment->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endcan --}}
                                @can('ratechart.assign')
                                    <button class="action-link" type="button" wire:click="deleteAssignment({{ $assignment->id }})" style="border:none; background:transparent; padding:0;">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No assignments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
