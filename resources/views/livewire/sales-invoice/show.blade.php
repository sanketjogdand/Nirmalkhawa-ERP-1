<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Invoice {{ $invoice->invoice_no }}</h2>
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <span class="btn-primary" style="pointer-events:none; opacity:0.8;">{{ $invoice->status }}</span>
            @if($invoice->is_locked)
                <span class="btn-danger" style="pointer-events:none; opacity:0.8;">Locked</span>
            @endif
        </div>
    </div>

    <div class="summary-container">
        <div class="summary-card">
            <div class="summary-heading">Header</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Invoice No</td>
                    <td>{{ $invoice->invoice_no }}</td>
                </tr>
                <tr>
                    <td class="label">Invoice Date</td>
                    <td>{{ $invoice->invoice_date ? $invoice->invoice_date->toDateString() : '' }}</td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td>{{ $invoice->customer->name ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">Remarks</td>
                    <td>{{ $invoice->remarks ?? '-' }}</td>
                </tr>
            </table>
        </div>
        <div class="summary-card">
            <div class="summary-heading">Totals</div>
            <table class="summary-table">
                <tr>
                    <td class="label">Subtotal</td>
                    <td>{{ number_format((float) $invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">GST Total</td>
                    <td>{{ number_format((float) $invoice->total_gst, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Grand Total</td>
                    <td>{{ number_format((float) $invoice->grand_total, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Locked By</td>
                    <td>{{ $invoice->lockedBy->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Locked At</td>
                    <td>{{ $invoice->locked_at ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Product</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Pack</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate / Kg</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Line Total</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Dispatch Ref</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->product->name ?? '' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->sale_mode }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ number_format((float) $line->computed_total_qty, 3) }} {{ $line->uom }}
                            @if($line->sale_mode === \App\Models\SalesInvoiceLine::MODE_BULK)
                                <div style="font-size:12px; color:gray;">Bulk: {{ number_format((float) $line->qty_bulk, 3) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($line->sale_mode === \App\Models\SalesInvoiceLine::MODE_PACK)
                                {{ $line->pack_count }} x {{ $line->pack_qty_snapshot ?? ($line->packSize->pack_qty ?? '') }} {{ $line->pack_uom ?? ($line->packSize->pack_uom ?? '') }}
                            @else
                                <span style="color:gray;">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $line->rate_per_kg, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $line->gst_rate_percent, 2) }}%</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $line->taxable_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $line->gst_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format((float) $line->line_total, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-size:12px;">
                            @if($line->dispatchLine)
                                <div>Dispatch: {{ $line->dispatchLine->dispatch->dispatch_no ?? '' }}</div>
                                <div>Line ID: {{ $line->dispatch_line_id }}</div>
                                <div style="color:gray;">Qty: {{ number_format((float) $line->dispatchLine->computed_total_qty, 3) }}</div>
                            @else
                                <span style="color:gray;">None</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No invoice lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:1rem;">
        <a href="{{ route('sales-invoices.view') }}" class="btn-primary" wire:navigate>Back to list</a>
    </div>
    @if (session('success'))
        <div class="toastr success">{{ session('success') }}</div>
    @endif
    @if (session('danger'))
        <div class="toastr danger">{{ session('danger') }}</div>
    @endif
</div>
