<div class="product-container">
    @php View::share('title_name', $title_name); @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">
            {{ $settlementId ? 'Edit Settlement' : 'New Settlement' }}
        </h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('center-settlements.view') }}" class="btn-primary" wire:navigate>Back to list</a>
        </div>
    </div>

    <form wire:submit.prevent="save">
        <div class="form-grid">
            <div class="form-group">
                <label for="center_id">Center</label>
                <select id="center_id" wire:model.live="center_id" class="input-field" required>
                    <option value="">Select center</option>
                    @foreach($centers as $center)
                        <option value="{{ $center->id }}">{{ $center->name }} ({{ $center->code }})</option>
                    @endforeach
                </select>
                @error('center_id') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="selectedMonth">Month</label>
                <input id="selectedMonth" type="month" wire:model.live="selectedMonth" class="input-field">
            </div>
            <div class="form-group">
                <label for="templateId">Template</label>
                <select id="templateId" wire:model.live="templateId" class="input-field">
                    <option value="">Default</option>
                    @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->start_day }}-{{ $template->end_of_month ? 'EOM' : $template->end_day }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="period_from">Period From</label>
                <input id="period_from" type="date" wire:model.live="period_from" class="input-field" required>
                @error('period_from') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="period_to">Period To</label>
                <input id="period_to" type="date" wire:model.live="period_to" class="input-field" required>
                @error('period_to') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="form-grid" style="margin-top:12px;">
            <div class="form-group">
                <label for="incentive_amount">Incentive</label>
                <input id="incentive_amount" type="number" step="0.01" min="0" wire:model.live="incentive_amount" class="input-field">
                @error('incentive_amount') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="advance_deducted">Advance Deducted</label>
                <input id="advance_deducted" type="number" step="0.01" min="0" wire:model.live="advance_deducted" class="input-field">
                @error('advance_deducted') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="short_adjustment">Short Adjustment</label>
                <input id="short_adjustment" type="number" step="0.01" min="0" wire:model.live="short_adjustment" class="input-field">
                @error('short_adjustment') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="other_deductions">Other Deductions</label>
                <input id="other_deductions" type="number" step="0.01" min="0" wire:model.live="other_deductions" class="input-field">
                @error('other_deductions') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="discount_amount">Discount</label>
                <input id="discount_amount" type="number" step="0.01" min="0" wire:model.live="discount_amount" class="input-field">
                @error('discount_amount') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group">
                <label for="tds_amount">TDS</label>
                <input id="tds_amount" type="number" step="0.01" min="0" wire:model.live="tds_amount" class="input-field">
                @error('tds_amount') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
            <div class="form-group span-2">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" wire:model.live="remarks" class="input-field" rows="3" placeholder="Optional remarks"></textarea>
                @error('remarks') <div style="color:#ef4444; font-size:13px;">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="margin:1rem 0; padding:16px; border-radius:8px;" class="border border-zinc-200 bg-white dark:bg-zinc-800 dark:border-zinc-700">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <div style="font-weight:600; font-size:16px;" class="dark:text-zinc-100">Preview</div>
                    <div style="color:gray; font-size:14px;" class="dark:text-zinc-300">{{ $previewCount }} milk intake rows in this period.</div>
                </div>
                <button type="button" class="btn-primary" wire:click="refreshPreview">Refresh Preview</button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin-top:12px;">
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">Total Qty (Ltr)</div>
                    <div style="font-size:18px; font-weight:700;">{{ number_format($previewTotals['total_qty_ltr'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">Gross Amount</div>
                    <div style="font-size:18px; font-weight:700;">₹{{ number_format($previewTotals['gross_amount_total'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">Commission</div>
                    <div style="font-size:18px; font-weight:700;">₹{{ number_format($previewTotals['commission_total'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">Net Total</div>
                    <div style="font-size:18px; font-weight:700;">₹{{ number_format($previewTotals['net_total'] ?? 0, 2) }}</div>
                </div>
            </div>
            <div style="margin-top:12px; display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">CM Qty (Ltr)</div>
                    <div style="font-size:16px; font-weight:600;">{{ number_format($previewTotals['cm_qty_ltr'] ?? 0, 2) }}</div>
                    <div style="font-size:12px; color:inherit; opacity:0.7;">Net ₹{{ number_format($previewTotals['cm_net'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100">
                    <div style="font-size:13px; color:inherit; opacity:0.7;">BM Qty (Ltr)</div>
                    <div style="font-size:16px; font-weight:600;">{{ number_format($previewTotals['bm_qty_ltr'] ?? 0, 2) }}</div>
                    <div style="font-size:12px; color:inherit; opacity:0.7;">Net ₹{{ number_format($previewTotals['bm_net'] ?? 0, 2) }}</div>
                </div>
            </div>
            @if($previewCount === 0)
                <div style="margin-top:10px; color:#ef4444;" class="dark:text-red-400">No unsettled milk intakes found in this period.</div>
            @endif
        </div>

        <div style="margin-top:12px;" class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Shift</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Qty (L)</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">FAT%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">SNF%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Linked</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($previewRows as $row)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['shift'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['milk_type'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row['qty_ltr'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row['fat_pct'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row['snf_pct'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                {{ $row['center_settlement_id'] ? 'Currently linked' : 'Unlinked' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No rows in preview.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px; display:flex; gap:12px;">
            <button type="submit" class="btn-primary">Save Settlement</button>
            <a href="{{ route('center-settlements.view') }}" class="btn-primary" style="background:#6b7280;" wire:navigate>Cancel</a>
        </div>
    </form>
</div>
