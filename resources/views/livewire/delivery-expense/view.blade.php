<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Delivery Expenses</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('deliveryexpense.create')
                <a href="{{ route('delivery-expenses.create') }}" class="btn-primary" wire:navigate>Add Expense</a>
            @endcan
        </div>
    </div>

    @if(session('error'))
        <div class="toastr danger" style="margin-top:0.5rem;">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="toastr success" style="margin-top:0.5rem;">{{ session('success') }}</div>
    @endif

    <div class="form-grid">
        <div class="form-group">
            <label for="dateFrom">Date From</label>
            <input id="dateFrom" type="date" wire:model.live="dateFrom" class="input-field">
        </div>
        <div class="form-group">
            <label for="dateTo">Date To</label>
            <input id="dateTo" type="date" wire:model.live="dateTo" class="input-field">
        </div>
        <div class="form-group">
            <label for="dispatchNo">Dispatch No</label>
            <input id="dispatchNo" type="text" wire:model.live="dispatchNo" class="input-field" placeholder="Dispatch number">
        </div>
        <div class="form-group">
            <label for="supplierId">Supplier</label>
            <select id="supplierId" wire:model.live="supplierId" class="input-field">
                <option value="">All</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="expenseType">Expense Type</label>
            <select id="expenseType" wire:model.live="expenseType" class="input-field">
                <option value="">All</option>
                @foreach($expenseTypes as $type)
                    <option value="{{ $type }}">{{ str_replace('_', ' ', $type) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="per-page-select-left" style="margin: 1rem 0; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;">
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" wire:change="updatePerPage" id="perPage">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div class="table-wrapper">
        <table class="product-table hover-highlight">
            <thead>
                <tr>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Dispatch</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Delivery Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Supplier</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Remarks</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expenses as $expense)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->expense_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($expense->dispatch)
                                <a href="{{ route('dispatches.show', $expense->dispatch->id) }}" class="action-link" wire:navigate>
                                    {{ $expense->dispatch->dispatch_no }}
                                </a>
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            {{ $expense->dispatch ? str_replace('_', ' ', $expense->dispatch->delivery_mode) : '—' }}
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->supplier?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ str_replace('_', ' ', $expense->expense_type) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($expense->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $expense->remarks }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                @can('deliveryexpense.update')
                                    <a href="{{ route('delivery-expenses.edit', $expense->id) }}" class="action-link" wire:navigate>Edit</a>
                                @endcan
                                @can('deliveryexpense.delete')
                                    <span aria-hidden="true">|</span>
                                    <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                        wire:click="deleteExpense({{ $expense->id }})"
                                        onclick="return confirm('Delete this expense?')">
                                        Delete
                                    </button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No expenses found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $expenses->links() }}
    </div>
</div>
