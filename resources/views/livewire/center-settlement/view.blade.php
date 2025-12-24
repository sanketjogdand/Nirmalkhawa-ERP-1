<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Center Settlements</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('centersettlement.create')
                <a href="{{ route('center-settlements.create') }}" class="btn-primary" wire:navigate>New Settlement</a>
            @endcan
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="centerId">Center</label>
            <select id="centerId" wire:model.live="centerId" class="input-field">
                <option value="">All</option>
                @foreach($centers as $center)
                    <option value="{{ $center['id'] }}">{{ $center['name'] }} ({{ $center['code'] ?? '' }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" wire:model.live="status" class="input-field">
                <option value="">All</option>
                <option value="{{ \App\Models\CenterSettlement::STATUS_DRAFT }}">Draft</option>
                <option value="{{ \App\Models\CenterSettlement::STATUS_FINAL }}">Final</option>
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

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model.live="perPage" wire:change="updatePerPage" id="perPage">
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
                    <th class="px-4 py-2 border dark:border-zinc-700">Settlement #</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Center</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Period</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty (Ltr)</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Net Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Status</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $row)
                    @php
                        $canEdit = auth()->user()->can('centersettlement.update') && ! $row->is_locked;
                    @endphp
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->settlement_no }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $row->center?->name }}
                            <div style="font-size:12px; color:gray;">{{ $row->center?->code }}</div>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ \Illuminate\Support\Carbon::parse($row->period_from)->format('d M Y') }}
                            -
                            {{ \Illuminate\Support\Carbon::parse($row->period_to)->format('d M Y') }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->total_qty_ltr, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row->net_total, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->status }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $row->is_locked ? 'Yes' : 'No' }}
                            @if($row->is_locked && $row->locked_at)
                                <div style="font-size:12px; color:gray;">{{ $row->locked_at->format('d M Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <a href="{{ route('center-settlements.show', $row->id) }}" class="action-link" wire:navigate>View</a>
                                @can('centersettlement.update')
                                    @if(! $row->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <a href="{{ route('center-settlements.edit', $row->id) }}" class="action-link" wire:navigate>Edit</a>
                                    @endif
                                @endcan
                                @can('centersettlement.finalize')
                                    @if(! $row->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" wire:click="confirmFinalize({{ $row->id }})" style="border:none; background:transparent; padding:0;">Finalize</button>
                                    @endif
                                @endcan
                                @can('centersettlement.unlock')
                                    @if($row->is_locked)
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" wire:click="confirmUnlock({{ $row->id }})" style="border:none; background:transparent; padding:0;">Unlock</button>
                                    @endif
                                @endcan
                                @can('centersettlement.update')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" wire:click="confirmDelete({{ $row->id }})" style="border:none; background:transparent; padding:0;">Cancel</button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No settlements found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $settlements->links() }}
    </div>

    @if($showFinalizeModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Finalize settlement?</h3>
                <p style="margin:8px 0;">Linked milk intakes will be locked from edits.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showFinalizeModal', false)">Cancel</button>
                    <button type="button" class="btn-primary" wire:click="finalizeConfirmed">Finalize</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUnlockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Unlock settlement?</h3>
                <p style="margin:8px 0;">Unlocking will move the settlement back to Draft.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showUnlockModal', false)">Cancel</button>
                    <button type="button" class="btn-primary" style="background:#059669;" wire:click="unlockConfirmed">Unlock</button>
                </div>
            </div>
        </div>
    @endif

    @if($showDeleteModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Cancel settlement?</h3>
                <p style="margin:8px 0;">Linked milk intakes will be released for future settlements.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showDeleteModal', false)">Back</button>
                    <button type="button" class="btn-danger" wire:click="deleteConfirmed">Cancel Settlement</button>
                </div>
            </div>
        </div>
    @endif
</div>
