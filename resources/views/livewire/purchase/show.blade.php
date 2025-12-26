<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Purchase</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('purchases.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if(! $purchase->is_locked)
                @can('purchase.update')
                    <a href="{{ route('purchases.edit', $purchase->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endcan
            @endif
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Purchase Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->purchase_date?->format('Y-m-d') }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->supplier?->name }}</td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Supplier Bill No</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->supplier_bill_no ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Supplier Bill Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->supplier_bill_date?->format('Y-m-d') ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->remarks ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Subtotal</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->subtotal, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">GST</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->total_gst, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Grand Total</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($purchase->grand_total, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Lock Status</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        {{ $purchase->is_locked ? 'Locked' : 'Open' }}
                        @if($purchase->is_locked)
                            <div style="font-size:12px; color:gray;">By: {{ $purchase->lockedBy?->name }} @if($purchase->locked_at) | {{ $purchase->locked_at->format('d M Y H:i') }} @endif</div>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $purchase->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

    <h3 style="margin:1rem 0 0.5rem;">Line Items</h3>
    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Description</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">UOM</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchase->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->product?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->description ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->qty, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->uom }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->rate, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->gst_rate_percent, 2) }}%</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->taxable_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->gst_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
