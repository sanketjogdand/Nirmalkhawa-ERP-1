<div style="margin-top:1.5rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h3 style="margin:0;">Delivery Expenses (Read Only)</h3>
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            <div><strong>Total:</strong> ₹{{ number_format($totalExpense, 2) }}</div>
        </div>
    </div>

    <div class="table-wrapper" style="margin-top:1rem;">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->expense_date?->format('Y-m-d') ?? $dispatch->dispatch_date?->toDateString() }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->supplier?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ str_replace('_', ' ', $expense->expense_type) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->remarks }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No expenses added.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
