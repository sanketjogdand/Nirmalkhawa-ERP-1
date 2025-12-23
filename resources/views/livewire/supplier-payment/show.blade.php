<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">Supplier Payment</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('supplier-payments.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('supplierpayment.update')
                <a href="{{ route('supplier-payments.edit', $payment->id) }}" class="btn-primary" wire:navigate>Edit Payment</a>
            @endcan
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->payment_date?->format('Y-m-d') }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        @if($payment->supplier)
                            <a href="{{ route('suppliers.show', $payment->supplier->id) }}" class="action-link" wire:navigate>{{ $payment->supplier->name }}</a>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Amount</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($payment->amount, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Payment Mode</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->payment_mode }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Company Account</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->company_account }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Reference No</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->reference_no }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->remarks }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $payment->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

</div>
