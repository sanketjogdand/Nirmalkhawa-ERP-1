<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Stock Adjustment #{{ $adjustment->id }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('inventory.stock-adjustments') }}" class="btn-secondary" wire:navigate>Back to List</a>
            @if(! $adjustment->is_locked)
                @can('stockadjustment.update')
                    <a href="{{ route('inventory.stock-adjustments.edit', $adjustment->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endcan
            @endif
        </div>
    </div>

    <div class="form-grid" style="margin-top:1rem;">
        <div class="form-group">
            <label>Date</label>
            <div class="input-field" style="background:#0f172a;">{{ $adjustment->adjustment_date?->format('Y-m-d') }}</div>
        </div>
        <div class="form-group">
            <label>Reason</label>
            <div class="input-field" style="background:#0f172a;">{{ $adjustment->reason }}</div>
        </div>
        <div class="form-group">
            <label>Locked</label>
            <div class="input-field" style="background:#0f172a;">
                @if($adjustment->is_locked)
                    Locked @if($adjustment->lockedBy) by {{ $adjustment->lockedBy->name }} @endif
                @else
                    No
                @endif
            </div>
        </div>
        <div class="form-group" style="grid-column: span 3;">
            <label>Remarks</label>
            <div class="input-field" style="background:#0f172a; min-height:48px;">{{ $adjustment->remarks ?? '—' }}</div>
        </div>
    </div>

    <div style="margin: 1rem 0;">
        <h3 style="margin:0; font-size:18px;">Lines</h3>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Direction</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjustment->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $line->product?->name }}
                            <div style="font-size:12px; color:gray;">{{ $line->product?->code }}</div>
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->direction }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->qty, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->remarks ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
