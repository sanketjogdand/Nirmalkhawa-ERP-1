<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Customer Receipt</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('customer-receipts.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('receipt.update')
                <a href="{{ route('customer-receipts.edit', $receipt->id) }}" class="btn-primary" wire:navigate>Edit Receipt</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->receipt_date?->format('Y-m-d') }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Customer</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        @if($receipt->customer)
                            <a href="{{ route('customers.show', $receipt->customer->id) }}" class="action-link" wire:navigate>{{ $receipt->customer->name }}</a>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Amount</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($receipt->amount, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Payment Mode</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->payment_mode }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Company Account</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->company_account }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Reference No</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->reference_no }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->remarks }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $receipt->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

</div>
