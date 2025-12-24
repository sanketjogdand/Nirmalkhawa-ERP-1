<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">
            Center Settlement Details
        </h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('center-settlements.view') }}" class="btn-primary" wire:navigate>Back to list</a>
            @can('centersettlement.update')
                @if(! $settlement->is_locked)
                    <a href="{{ route('center-settlements.edit', $settlement->id) }}" class="btn-primary" wire:navigate>Edit</a>
                @endif
            @endcan
        </div>
    </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; margin-top:12px;">
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
                <div style="font-size:13px; color:gray;">Center</div>
                <div style="font-size:16px; font-weight:600;">{{ $settlement->center?->name }}</div>
                <div style="font-size:12px; color:gray;">{{ $settlement->center?->code }}</div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
                <div style="font-size:13px; color:gray;">Period</div>
                <div style="font-size:16px; font-weight:600;">
                    {{ $settlement->period_from?->format('d M Y') }} - {{ $settlement->period_to?->format('d M Y') }}
                </div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
                <div style="font-size:13px; color:gray;">Lock Status</div>
                <div style="font-size:16px; font-weight:600;">{{ $settlement->is_locked ? 'Locked' : 'Unlocked' }}</div>
                @if($settlement->lockedBy)
                    <div style="font-size:12px; color:gray;">By: {{ $settlement->lockedBy?->name }}</div>
                @endif
                @if($settlement->locked_at)
                    <div style="font-size:12px; color:gray;">At: {{ $settlement->locked_at->format('d M Y H:i') }}</div>
                @endif
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
                <div style="font-size:13px; color:gray;">Notes</div>
                <div style="font-size:14px;">{{ $settlement->notes ?: '—' }}</div>
            </div>
        </div>

    <div style="margin-top:16px; padding:16px; border-radius:8px;" class="dark:bg-zinc-800 border dark:border-zinc-700">
        <div style="font-weight:700; font-size:16px; margin-bottom:8px;">Totals</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">Total Qty (Ltr)</div>
                <div style="font-size:18px; font-weight:700;">{{ number_format($settlement->total_qty_ltr, 2) }}</div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">Gross</div>
                <div style="font-size:18px; font-weight:700;">₹{{ number_format($settlement->gross_amount_total, 2) }}</div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">Commission</div>
                <div style="font-size:18px; font-weight:700;">₹{{ number_format($settlement->commission_total, 2) }}</div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">Net</div>
                <div style="font-size:18px; font-weight:700;">₹{{ number_format($settlement->net_total, 2) }}</div>
            </div>
        </div>
        <div style="margin-top:12px; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">CM Qty (Ltr)</div>
                <div style="font-size:16px; font-weight:600;">{{ number_format($settlement->cm_qty_ltr, 2) }}</div>
                <div style="font-size:12px; color:gray;">Net ₹{{ number_format($settlement->cm_net, 2) }}</div>
            </div>
            <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                <div style="font-size:13px; color:gray;">BM Qty (Ltr)</div>
                <div style="font-size:16px; font-weight:600;">{{ number_format($settlement->bm_qty_ltr, 2) }}</div>
                <div style="font-size:12px; color:gray;">Net ₹{{ number_format($settlement->bm_net, 2) }}</div>
            </div>
        </div>
    </div>

    <div style="margin-top:16px;">
        <h3 style="font-size:18px; font-weight:700; margin-bottom:8px;">Milk Intake Rows</h3>
        <div class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Shift</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Qty (L)</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">FAT%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">SNF%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Rate/Ltr</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Gross</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Commission</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settlement->milkIntakes as $row)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($row->date)->format('d M Y') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->shift }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row->milk_type }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->qty_ltr, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->fat_pct, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row->snf_pct, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row->rate_per_ltr ?? 0, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row->amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row->commission_amount ?? 0, 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row->net_amount ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No milk intake rows linked.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
