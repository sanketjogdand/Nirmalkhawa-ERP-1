<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <h2 class="page-heading" style="margin-bottom: 0;">Employee Payments</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @can('employee_payment.create')
                <a href="{{ route('employee-payments.create') }}" class="btn-primary" wire:navigate>Add Payment</a>
            @endcan
        </div>
    </div>

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
            <label for="employeeId">Employee</label>
            <select id="employeeId" wire:model.live="employeeId" class="input-field">
                <option value="">All</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="paymentType">Type</label>
            <select id="paymentType" wire:model.live="paymentType" class="input-field">
                <option value="">All</option>
                <option value="ADVANCE">ADVANCE</option>
                <option value="SALARY">SALARY</option>
                <option value="OTHER">OTHER</option>
            </select>
        </div>
        <div class="form-group">
            <label for="lockedFilter">Locked</label>
            <select id="lockedFilter" wire:model.live="lockedFilter" class="input-field">
                <option value="">All</option>
                <option value="1">Locked</option>
                <option value="0">Unlocked</option>
            </select>
        </div>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin: 1rem 0;">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
            @can('employee_payment.lock')
                <button type="button" class="btn-primary" wire:click="confirmLock()" @disabled(count($selected) === 0)>Bulk Lock</button>
            @endcan
            @can('employee_payment.unlock')
                <button type="button" class="btn-primary" style="background:#059669;" wire:click="confirmUnlock()" @disabled(count($selected) === 0)>Bulk Unlock</button>
            @endcan
        </div>
        <div class="per-page-select" style="margin-left:auto;">
            <label for="perPage">Records per page:</label>
            <select wire:model="perPage" id="perPage">
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
                    <th class="px-4 py-2 border dark:border-zinc-700" style="width:36px;">
                        <input type="checkbox" wire:model.live="selectAll" onclick="event.stopPropagation();" aria-label="Select all on this page">
                    </th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Employee</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Type</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Mode</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Account</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Locked</th>
                    <th class="px-4 py-2 border dark:border-zinc-700">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            <input type="checkbox" value="{{ $record->id }}" wire:model.live="selected" @disabled($record->is_locked) onclick="event.stopPropagation();">
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $record->payment_date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $record->employee?->name }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $record->payment_type }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($record->amount, 2) }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $record->payment_mode ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">{{ $record->company_account ?? '—' }}</td>
                        <td class="px-4 py-2 border dark:border-zinc-700">
                            @if($record->is_locked)
                                <span title="By {{ $record->lockedBy?->name }} at {{ $record->locked_at?->format('d M Y H:i') }}">Locked</span>
                            @else
                                No
                            @endif
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="white-space:nowrap;">
                            <span style="display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                @if(! $record->is_locked)
                                    @can('employee_payment.update')
                                        <a href="{{ route('employee-payments.edit', $record->id) }}" class="action-link" wire:navigate>Edit</a>
                                    @endcan
                                    @can('employee_payment.delete')
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="delete({{ $record->id }})"
                                            onclick="return confirm('Delete this payment?')">
                                            Delete
                                        </button>
                                    @endcan
                                    @can('employee_payment.lock')
                                        <span aria-hidden="true">|</span>
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="confirmLock({{ $record->id }})">
                                            Lock
                                        </button>
                                    @endcan
                                @else
                                    @can('employee_payment.unlock')
                                        <button type="button" class="action-link" style="border:none; background:transparent; padding:0;"
                                            wire:click="confirmUnlock({{ $record->id }})">
                                            Unlock
                                        </button>
                                    @endcan
                                @endif
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination-wrapper">
        {{ $records->links() }}
    </div>

    @if($showLockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Lock payments?</h3>
                <p style="margin:8px 0;">This cannot be undone.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showLockModal', false)">Cancel</button>
                    <button type="button" class="btn-danger" wire:click="lockConfirmed">Lock Now</button>
                </div>
            </div>
        </div>
    @endif

    @if($showUnlockModal)
        <div style="position:fixed; inset:0; background:rgba(0,0,0,0.65); display:flex; align-items:center; justify-content:center; z-index:1000;">
            <div style="background:#111827; color:#e5e7eb; padding:20px; border-radius:12px; max-width:420px; width:90%; border:1px solid #374151;">
                <h3 style="margin-top:0; font-size:18px;">Unlock payments?</h3>
                <p style="margin:8px 0;">Unlocking allows edits.</p>
                <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:16px;">
                    <button type="button" class="btn-primary" style="background:#6b7280;" wire:click="$set('showUnlockModal', false)">Cancel</button>
                    <button type="button" class="btn-primary" style="background:#059669;" wire:click="unlockConfirmed">Unlock Now</button>
                </div>
            </div>
        </div>
    @endif
</div>
