<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Material Consumption</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('material-consumptions.view') }}" class="btn-secondary" wire:navigate>Back</a>
            @can('materialconsumption.update')
                @if(! $consumption->is_locked)
                    <a href="{{ route('material-consumptions.edit', $consumption->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endif
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $consumption->consumption_date?->format('Y-m-d') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Type</th><td class="px-4 py-2 border dark:border-zinc-700">{{ config('material_consumption.types')[$consumption->consumption_type] ?? $consumption->consumption_type }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $consumption->remarks ?: '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Lock Status</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        {{ $consumption->is_locked ? 'Locked' : 'Open' }}
                        @if($consumption->is_locked)
                            <div style="font-size:12px; color:gray;">By: {{ $consumption->lockedBy?->name }} @if($consumption->locked_at) | {{ $consumption->locked_at->format('d M Y H:i') }} @endif</div>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $consumption->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $consumption->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $consumption->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

    <h3 style="margin:1rem 0 0.5rem;">Lines</h3>
    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($consumption->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $line->product?->name }}
                            @if($line->product?->is_packing)
                                <span style="background:#2563eb; color:white; padding:2px 6px; border-radius:6px; font-size:12px;">Packing</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->qty ?? 0, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->uom ?? $line->product?->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
