<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Milk Intake</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('milkintake.create')
                <a href="{{ route('milk-intakes.create') }}" class="btn-primary" wire:navigate>Add Intake</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="search">Search Center</label>
            <input id="search" type="text" wire:model.live="search" class="input-field" placeholder="Center name or code">
        </div>
        <div class="form-group">
            <label for="centerId">Center</label>
            <select id="centerId" wire:model.live="centerId" class="input-field">
                <option value="">All</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="shift">Shift</label>
            <select id="shift" wire:model.live="shift" class="input-field">
                <option value="">All</option>
                <option value="{{ \App\Models\MilkIntake::SHIFT_MORNING }}">Morning</option>
                <option value="{{ \App\Models\MilkIntake::SHIFT_EVENING }}">Evening</option>
            </select>
        </div>
        <div class="form-group">
            <label for="milkType">Milk Type</label>
            <select id="milkType" wire:model.live="milkType" class="input-field">
                <option value="">All</option>
                <option value="CM">Cow Milk</option>
                <option value="BM">Buffalo Milk</option>
            </select>
        </div>
        <div class="form-group">
            <label for="fromDate">From</label>
            <input id="fromDate" type="date" wire:model.live="fromDate" class="input-field">
        </div>
        <div class="form-group">
            <label for="toDate">To</label>
            <input id="toDate" type="date" wire:model.live="toDate" class="input-field">
        </div>
    </div>

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @can('milkintake.apply_ratechart')
                <button type="button" class="btn-primary" wire:click="startApplyRateChart">Apply Rate Chart</button>
            @endcan
            @can('milkintake.lock')
                <button type="button" class="btn-primary" style="background-color:#f97316;" wire:click="confirmLock">Lock Selected</button>
            @endcan
            @can('milkintake.unlock')
                <button type="button" class="btn-primary" style="background-color:#059669;" wire:click="confirmUnlock">Unlock Selected</button>
            @endcan
        </div>
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
                <option value="5">5</option>
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
                    <th class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">
                        <input type="checkbox" wire:model.live="selectAll" />
                    </th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Center</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Shift</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty (L)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty (KG)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">FAT%</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">SNF%</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate/Ltr</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Commission</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Net Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Kg FAT</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Kg SNF</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($intakes as $row)
                    @php
                        $highlight = $row->rate_per_ltr === null || $row->rate_per_ltr == 0;
                        $canUnlock = auth()->user()->can('milkintake.unlock');
                        $inSettlement = $row->center_settlement_id !== null;
                        $settlementLocked = $row->centerSettlement && $row->centerSettlement->is_locked && $row->centerSettlement->status === \App\Models\CenterSettlement::STATUS_FINAL;
                        $checkboxDisabled = $settlementLocked || ($row->is_locked && ! $canUnlock);
                    @endphp
                    <tr @if($highlight) style="background-color:#fff3cd;" @endif>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="checkbox" value="{{ $row->id }}" wire:model.live="selected" @if($checkboxDisabled) disabled @endif>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $row->center?->name }}
                            <div style="font-size:12px; color:gray;">{{ $row->center?->code }}</div>
                            @if($inSettlement)
                                <div style="font-size:12px; color:#f97316;">Settlement: {{ $row->centerSettlement?->settlement_no }} ({{ $row->centerSettlement?->status }})</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->shift }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->milk_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->qty_ltr, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->qty_kg, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->fat_pct, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->snf_pct, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($row->rate_per_ltr)
                                ₹{{ number_format($row->rate_per_ltr, 2) }}
                            @else
                                <span style="color:orange;">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($row->amount)
                                ₹{{ number_format($row->amount, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            ₹{{ number_format($row->commission_amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($row->net_amount !== null)
                                ₹{{ number_format($row->net_amount, 2) }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->kg_fat, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->kg_snf, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->rate_status }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $row->is_locked ? 'Yes' : 'No' }}
                            @if($row->is_locked && $row->locked_at)
                                <div style="font-size:12px; color:gray;">{{ $row->locked_at->format('d M Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            @php $actions = []; @endphp
                            @if(! $settlementLocked)
                                @can('milkintake.update')
                                    @if(! $row->is_locked)
                                        @php $actions[] = '<a href="'.route('milk-intakes.edit', $row->id).'" class="action-link" wire:navigate>Edit</a>'; @endphp
                                    @endif
                                @endcan
                                @can('milkintake.rate.override')
                                    @if(! $row->is_locked)
                                        @php $actions[] = '<button type="button" class="action-link" wire:click="openOverride('.$row->id.')" style="border:none; background:transparent; padding:0;">Manual Rate</button>'; @endphp
                                    @endif
                                @endcan
                                @can('milkintake.lock')
                                    @if(! $row->is_locked)
                                        @php $actions[] = '<button type="button" class="action-link" wire:click="confirmLock('.$row->id.')" style="border:none; background:transparent; padding:0;">Lock</button>'; @endphp
                                    @endif
                                @endcan
                                @can('milkintake.unlock')
                                    @if($row->is_locked)
                                        @php $actions[] = '<button type="button" class="action-link" wire:click="confirmUnlock('.$row->id.')" style="border:none; background:transparent; padding:0;">Unlock</button>'; @endphp
                                    @endif
                                @endcan
                                @can('milkintake.delete')
                                    @if(! $row->is_locked)
                                        @php $actions[] = '<button type="button" class="action-link" wire:click="confirmDelete('.$row->id.')" style="border:none; background:transparent; padding:0;">Delete</button>'; @endphp
                                    @endif
                                @endcan
                            @endif

                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                @foreach($actions as $index => $action)
                                    {!! $action !!}
                                    @if($index < count($actions) - 1)
                                        <span aria-hidden="true">|</span>
                                    @endif
                                @endforeach
                                @if(empty($actions) && $settlementLocked)
                                    <span style="color:#f97316;">Finalized</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="16" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $intakes->links() }}
    </div>

    @if($showLockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Lock records?</h3>
                <p style="margin:8px 0;">This cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showLockModal', false)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="lockConfirmed">Lock Now</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUnlockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Unlock records?</h3>
                <p style="margin:8px 0;">Unlocking allows edits and recalculation.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showUnlockModal', false)">Cancel</button>
                    <button type="button" class="btn-primary" style="background:#059669;" wire:click="unlockConfirmed">Unlock Now</button>
                </div>
            </div>
        </div>
    @endif

    @if($showApplyModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:440px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Apply Rate Chart</h3>
                <p style="margin:8px 0;">Do you want to auto-calculate MANUAL records in the selected rows?</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showApplyModal', false)">No, keep manual</button>
                    <button type="button" class="btn-primary" wire:click="applyRateChart(true)">Yes, include manual</button>
                </div>
            </div>
        </div>
    @endif

    @if($showOverrideModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:460px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Manual Rate Override</h3>
                <div class="form-group">
                    <label for="override_rate_per_ltr">Rate per Liter</label>
                    <input id="override_rate_per_ltr" type="number" step="0.01" wire:model="override_rate_per_ltr" class="input-field">
                    @error('override_rate_per_ltr') <span style="color:#fca5a5;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="override_reason">Reason (optional)</label>
                    <textarea id="override_reason" wire:model="override_reason" class="input-field" rows="3" placeholder="Reason for manual override"></textarea>
                    @error('override_reason') <span style="color:#fca5a5;">{{ $message }}</span> @enderror
                </div>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="cancelOverride">Cancel</button>
                    <button type="button" class="btn-primary" wire:click="saveOverride">Save</button>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:440px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Delete intake?</h3>
                <p style="margin:8px 0;">This will create a reversal entry in inventory.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showDeleteModal', false)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="deleteConfirmed">Delete</button>
                </div>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif

    @if (session('info'))
        <div class="toastr info">{{ session('info') }}</div>
    @endif

    @if (session('warning'))
        <div class="toastr warning">{{ session('warning') }}</div>
    @endif

    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
