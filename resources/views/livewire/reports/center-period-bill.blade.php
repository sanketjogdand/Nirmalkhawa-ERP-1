<div class="product-container">
    @php
        View::share('title_name', $title_name ?? $title);
        $pdfUrl = null;
        if ($centerId && $selectedMonth && $templateId) {
            $pdfUrl = route('reports.employees-centers.center-period-bill.pdf', [
                'center_id' => $centerId,
                'month' => $selectedMonth,
                'template_id' => $templateId,
            ]);
        }
    @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <h2 class="page-heading" style="margin-bottom:0;">{{ $title }}</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            @if($pdfUrl)
                <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="btn-primary">Open PDF</a>
            @else
                <button type="button" class="btn-primary" style="background:#6b7280;" disabled>Open PDF</button>
            @endif
        </div>
    </div>

    <div class="form-grid">
        <div class="form-group">
            <label for="centerId">Center</label>
            <select id="centerId" wire:model.live="centerId" class="input-field" required>
                <option value="">Select center</option>
                @foreach($centers as $center)
                    <option value="{{ $center['value'] }}">{{ $center['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="selectedMonth">Month</label>
            <input id="selectedMonth" type="month" wire:model.live="selectedMonth" class="input-field" required>
        </div>
        <div class="form-group">
            <label for="templateId">Template</label>
            <select id="templateId" wire:model.live="templateId" class="input-field" required>
                <option value="">Select template</option>
                @foreach($templates as $template)
                    <option value="{{ $template['value'] }}">{{ $template['label'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(! $centerId || ! $selectedMonth || ! $templateId)
        <div style="margin-top:16px; color:#ef4444;" class="dark:text-red-400">Select a center, month, and template to view the report.</div>
    @elseif(! $periodFrom || ! $periodTo)
        <div style="margin-top:16px; color:#ef4444;" class="dark:text-red-400">Select a valid period template.</div>
    @else
        <div style="margin-top:16px; padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
            <div style="font-weight:600;">Period</div>
            <div style="color:gray;">{{ \Illuminate\Support\Carbon::parse($periodFrom)->format('d M Y') }} to {{ \Illuminate\Support\Carbon::parse($periodTo)->format('d M Y') }}</div>
        </div>

        <div style="margin-top:16px;" class="table-wrapper">
            <table class="product-table hover-highlight">
                <thead>
                    <tr>
                        <th class="px-4 py-2 border dark:border-zinc-700">Date</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Shift</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Milk Type</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Qty</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">FAT%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">SNF%</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Rate</th>
                        <th class="px-4 py-2 border dark:border-zinc-700">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['shift'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['milk_type'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ number_format($row['qty'], 2) }} {{ $row['qty_unit'] }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['fat_pct'] === null ? '-' : number_format($row['fat_pct'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">{{ $row['snf_pct'] === null ? '-' : number_format($row['snf_pct'], 2) }}</td>
                            <td class="px-4 py-2 border dark:border-zinc-700">
                                {{ $row['rate'] === null ? '-' : '₹'.number_format($row['rate'], 2) }}
                            </td>
                            <td class="px-4 py-2 border dark:border-zinc-700">₹{{ number_format($row['amount'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-2 border dark:border-zinc-700" style="text-align:center;">No milk intake rows found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td class="px-4 py-2 border dark:border-zinc-700" colspan="3" style="font-weight:600;">Totals</td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600;">
                            {{ number_format($totals['qty_ltr'] ?? 0, 2) }} L
                        </td>
                        <td class="px-4 py-2 border dark:border-zinc-700" colspan="3"></td>
                        <td class="px-4 py-2 border dark:border-zinc-700" style="font-weight:600;">
                            ₹{{ number_format($totals['amount'] ?? 0, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top:16px; padding:16px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
            <div style="font-weight:700; font-size:16px; margin-bottom:8px;">Payable Details</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px;">
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Gross Amount</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['gross_amount_total'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Commission</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['commission_total'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Incentive</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['incentive_amount'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Advance Given</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['advance_given'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Advance Deducted</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['advance_deducted'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Short Adjustment</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['short_adjustment'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Other Deductions</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['other_deductions'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Discount</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['discount_amount'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">TDS</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['tds_amount'] ?? 0, 2) }}</div>
                </div>
                <div style="padding:12px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-700">
                    <div style="font-size:13px; color:gray;">Settlement Net Total</div>
                    <div style="font-size:16px; font-weight:600;">₹{{ number_format($payable['net_total'] ?? 0, 2) }}</div>
                </div>
            </div>
            @if(! $hasSettlement)
                <div style="margin-top:10px; color:gray; font-size:13px;">No settlement found for this period. Gross is derived from intakes; other fields are 0.</div>
            @endif
        </div>

        <div style="margin-top:16px; padding:16px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
            <div style="font-weight:700; font-size:16px;">Net Payable Till {{ \Illuminate\Support\Carbon::parse($periodTo)->format('d M Y') }}</div>
            <div style="font-size:20px; font-weight:700; margin-top:6px;">₹{{ number_format($netPayableTillEnd ?? 0, 2) }}</div>
        </div>

        <div style="margin-top:16px; padding:16px; border-radius:8px;" class="border dark:border-zinc-700 dark:bg-zinc-800">
            <div style="font-weight:700; font-size:16px;">Advance Outstanding Till {{ \Illuminate\Support\Carbon::parse($periodTo)->format('d M Y') }}</div>
            <div style="font-size:20px; font-weight:700; margin-top:6px;">₹{{ number_format($advanceOutstandingTillEnd ?? 0, 2) }}</div>
        </div>
    @endif
</div>
