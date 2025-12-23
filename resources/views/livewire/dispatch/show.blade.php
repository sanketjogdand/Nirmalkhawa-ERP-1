<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Dispatch {{ $dispatch->dispatch_no }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('dispatches.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('dispatch.update')
                @if(! $dispatch->is_locked)
                    <a href="{{ route('dispatches.edit', $dispatch->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="form-grid" style="margin-top:1rem;">
        <div class="form-group">
            <label>Date</label>
            <div>{{ $dispatch->dispatch_date ? $dispatch->dispatch_date->toDateString() : '' }}</div>
        </div>
        <div class="form-group">
            <label>Delivery Mode</label>
            <div>{{ str_replace('_', ' ', $dispatch->delivery_mode) }}</div>
        </div>
        <div class="form-group">
            <label>Status</label>
            <div>{{ $dispatch->status }}</div>
        </div>
        <div class="form-group">
            <label>Locked</label>
            <div>{{ $dispatch->is_locked ? 'Yes' : 'No' }}</div>
        </div>
        <div class="form-group">
            <label>Vehicle</label>
            <div>{{ $dispatch->vehicle_no ?: '—' }}</div>
        </div>
        <div class="form-group">
            <label>Driver</label>
            <div>{{ $dispatch->driver_name ?: '—' }}</div>
        </div>
        <div class="form-group span-2">
            <label>Remarks</label>
            <div>{{ $dispatch->remarks ?: '—' }}</div>
        </div>
        <div class="form-group">
            <label>Created By</label>
            <div>{{ $dispatch->createdBy->name ?? '—' }}</div>
        </div>
        <div class="form-group">
            <label>Locked By</label>
            <div>{{ $dispatch->lockedBy->name ?? '—' }}</div>
        </div>
    </div>

    <h3 style="margin:1.5rem 0 0.5rem 0;">Lines</h3>
    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Customer</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Invoice</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty / Packs</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dispatch->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->customer->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->invoice_id ?: '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->product->name ?? 'N/A' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->sale_mode }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($line->sale_mode === \App\Models\DispatchLine::MODE_BULK)
                                {{ $line->qty_bulk }} {{ $line->uom }}
                            @else
                                {{ $line->pack_count }} x {{ $line->pack_qty_snapshot }} {{ $line->pack_uom }}
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->computed_total_qty }} {{ $line->uom }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @livewire('delivery-expense.manage', ['dispatchId' => $dispatch->id], key('dispatch-expenses-'.$dispatch->id))
</div>
