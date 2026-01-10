<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">General Expense</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('general-expenses.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @if(! $expense->is_locked)
                @can('general_expense.update')
                    <a href="{{ route('general-expenses.edit', $expense->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endcan
            @endif
            @can('general_expense_payment.view')
                <a href="{{ route('general-expense-payments.view') }}" class="btn-primary" wire:navigate>Payments</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif
    @if(session('danger'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('danger') }}</div>
    @endif

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table">
            <tbody>
                <tr><th class="px-4 py-2 border dark:border-zinc-700" style="width:220px;">Expense Date</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->expense_date?->format('Y-m-d') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Supplier</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->supplier?->name ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Invoice No</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->invoice_no ?? '—' }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Remarks</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->remarks ?? '—' }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Attachment</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        @if($expense->attachment_path)
                            <a href="{{ asset('storage/'.$expense->attachment_path) }}" target="_blank" rel="noopener" class="action-link">View attachment</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Taxable Total</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($taxable_total, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">GST Total</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($gst_total, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Expense Total</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($expense_total, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Paid Total</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($paid_total, 2) }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Balance</th><td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($balance, 2) }}</td></tr>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Lock Status</th>
                    <td class="px-4 py-2 border dark:border-zinc-700">
                        {{ $expense->is_locked ? 'Locked' : 'Open' }}
                        @if($expense->is_locked)
                            <div style="font-size:12px; color:gray;">By: {{ $expense->lockedBy?->name }} @if($expense->locked_at) | {{ $expense->locked_at->format('d M Y H:i') }} @endif</div>
                        @endif
                    </td>
                </tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created By</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->createdBy?->name }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Created At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->created_at?->format('d M Y, h:i A') }}</td></tr>
                <tr><th class="px-4 py-2 border dark:border-zinc-700">Updated At</th><td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->updated_at?->format('d M Y, h:i A') }}</td></tr>
            </tbody>
        </table>
    </div>

    <h3 style="margin:1rem 0 0.5rem;">Line Items</h3>
    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Category</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Description</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Taxable</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST %</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">GST Amt</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expense->lines as $line)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->category?->name ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->description ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($line->qty, 3) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->rate !== null ? number_format($line->rate, 2) : '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->taxable_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $line->gst_rate !== null ? number_format($line->gst_rate, 2) : '0.00' }}%</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->gst_amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($line->total_amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No lines.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
