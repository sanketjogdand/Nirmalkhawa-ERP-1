<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">GRN Details</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('grns.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if(! $grn->is_locked)
                @can('grn.update')
                    <a href="{{ route('grns.edit', $grn->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endcan
            @endif
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">GRN Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->grn_date?->format('Y-m-d') }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->supplier?->name }}</td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Purchase Ref</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->purchase?->supplier_bill_no ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->remarks ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Lock Status</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        {{ $grn->is_locked ? 'Locked' : 'Open' }}
                        @if($grn->is_locked)
                            <div style="font-size:12px; color:gray;">By: {{ $grn->lockedBy?->name }} @if($grn->locked_at) | {{ $grn->locked_at->format('d M Y H:i') }} @endif</div>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $grn->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

    <h3 style="margin:1rem 0 0.5rem;">Received Lines</h3>
    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Received Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grn->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->product?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->received_qty, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->remarks ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
